<?php
require_once __DIR__ . '/includes/auth.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إنشاء كائن من فئة Auth
$auth = new Auth();

// تسجيل الدخول إلى سجل الأخطاء
error_log("Accessing site_structure.php, Session ID: " . session_id() . ", user_id: " . ($_SESSION['user_id'] ?? 'unset') . ", user_role: " . ($_SESSION['user_role'] ?? 'unset'), 3, __DIR__ . "/log.txt");

// التحقق من تسجيل الدخول ودور المشرف باستخدام كائن Auth
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    error_log("Not logged in or not admin, redirecting", 3, __DIR__ . "/log.txt");
    if (isset($_GET['redirected']) && (int)$_GET['redirected'] > 3) {
        error_log("Redirect loop detected", 3, __DIR__ . "/log.txt");
        die("خطأ: حلقة توجيه مكتشفة. امسح ملفات تعريف الارتباط.");
    }
    $redirect_count = isset($_GET['redirected']) ? (int)$_GET['redirected'] + 1 : 1;
    header("Location: /admin/admin_login.php?redirected=$redirect_count");
    exit;
}

// منع التخزين المؤقت
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// توليد رمز تسجيل الخروج
if (!isset($_SESSION['logout_token'])) {
    $_SESSION['logout_token'] = bin2hex(random_bytes(32));
}

// قاموس الأوصاف
$descriptions = [
    'includes' => 'ملفات الإعدادات والدوال المشتركة.',
    'Uploads' => 'يخزن الملفات المرفوعة مثل صور المنتجات.',
    'products' => 'صور المنتجات المرفوعة.',
    'admin' => 'صفحات إدارة الموقع.',
    'index.php' => 'الصفحة الرئيسية، تعرض قائمة المنتجات.',
    'site_structure.php' => 'يعرض هيكلية الموقع.',
    'config.php' => 'إعدادات قاعدة البيانات والموقع.',
    '*.jpg' => 'صورة منتج.',
    '*.png' => 'صورة منتج.',
    '*.webp' => 'صورة منتج.',
    '*.php' => 'ملف PHP لتنفيذ منطق الموقع.',
    '*.html' => 'صفحة ويب ثابتة.',
    '*.css' => 'ملف تنسيق CSS.',
    '*.js' => 'ملف JavaScript.'
];

// دالة اختبار الصفحة
function testPage($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [
        'status' => $http_code === 200 ? 'يعمل' : ($http_code === 404 ? 'غير موجود' : "حالة: $http_code"),
        'class' => $http_code === 200 ? 'text-success' : ($http_code === 404 ? 'text-danger' : 'text-warning'),
        'code' => $http_code
    ];
}

// دالة استكشاف الملفات
function scanDirectory($dir, $level = 0, $filter = '', $search = '') {
    $files = [];
    $dirs = [];
    if (!is_dir($dir)) {
        error_log("Directory not found: $dir", 3, __DIR__ . "/log.txt");
        return ['files' => [], 'dirs' => [], 'error' => "المجلد $dir غير موجود."];
    }
    if (!is_readable($dir)) {
        error_log("Directory not readable: $dir", 3, __DIR__ . "/log.txt");
        return ['files' => [], 'dirs' => [], 'error' => "لا يمكن قراءة المجلد $dir."];
    }
    $handle = @opendir($dir);
    if ($handle === false) {
        error_log("Failed to open directory: $dir", 3, __DIR__ . "/log.txt");
        return ['files' => [], 'dirs' => [], 'error' => "فشل في فتح المجلد $dir."];
    }
    while (($entry = readdir($handle)) !== false) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . '/' . $entry;
        if ($search && stripos($entry, $search) === false) continue;
        if (is_dir($path)) {
            $dirs[] = [
                'name' => $entry,
                'path' => $path,
                'level' => $level,
                'permissions' => substr(sprintf('%o', fileperms($path)), -4),
                'modified' => date('Y-m-d H:i:s', filemtime($path))
            ];
        } elseif (is_file($path)) {
            if ($filter && !fnmatch($filter, $entry)) continue;
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            $is_page = in_array($ext, ['php', 'html', 'css', 'js']);
            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
            $test_result = ($is_page || $is_image) ? testPage("https://doublepower.org/" . str_replace(__DIR__ . '/', '', $path)) : null;
            $files[] = [
                'name' => $entry,
                'path' => $path,
                'level' => $level,
                'size' => filesize($path),
                'permissions' => substr(sprintf('%o', fileperms($path)), -4),
                'modified' => date('Y-m-d H:i:s', filemtime($path)),
                'is_page' => $is_page,
                'is_image' => $is_image,
                'test_result' => $test_result
            ];
        }
    }
    closedir($handle);
    error_log("Scanned $dir: " . count($dirs) . " dirs, " . count($files) . " files", 3, __DIR__ . "/log.txt");
    usort($dirs, fn($a, $b) => strcmp($a['name'], $b['name']));
    usort($files, fn($a, $b) => strcmp($a['name'], $b['name']));
    foreach ($dirs as &$dir) {
        $sub = scanDirectory($dir['path'], $level + 1, $filter, $search);
        $dir['subdirs'] = $sub['dirs'];
        $dir['subfiles'] = $sub['files'];
    }
    return ['files' => $files, 'dirs' => $dirs, 'error' => null];
}

// دالة إحصائيات الخادم
function getServerStats() {
    $disk_total = disk_total_space(__DIR__);
    $disk_free = disk_free_space(__DIR__);
    $disk_used = $disk_total - $disk_free;
    $memory = ini_get('memory_limit');

    return [
        'disk_total' => formatSize($disk_total),
        'disk_used' => formatSize($disk_used),
        'disk_free' => formatSize($disk_free),
        'memory_limit' => $memory,
    ];
}

// دالة تنسيق الحجم
function formatSize($bytes) {
    if ($bytes >= 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    if ($bytes >= 1024 * 1024) return number_format($bytes / (1024 * 1024), 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

// دالة عرض الشجرة
function displayTree($dirs, $files) {
    $output = '';
    foreach ($dirs as $dir) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $dir['level']);
        $description = getDescription($dir['name']);
        $toggle_icon = (!empty($dir['subdirs']) || !empty($dir['subfiles'])) ? '<i class="fas fa-chevron-right toggle-icon me-2"></i>' : '';
        $output .= "<li class='tree-item'>
            {$indent}{$toggle_icon}<i class='fas fa-folder text-warning me-2'></i>" . htmlspecialchars($dir['name']) . "
            <span class='text-muted small'>{$description}</span>
            <span class='ms-2'>[{$dir['permissions']}]</span>
            <span class='ms-2'>{$dir['modified']}</span>
            <ul class='tree-list collapse' id='tree-" . md5($dir['path']) . "'>" . displayTree($dir['subdirs'], $dir['subfiles']) . "</ul>
        </li>";
    }
    foreach ($files as $file) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $file['level']);
        $icon = $file['is_image'] ? 'fas fa-image text-success' : ($file['is_page'] ? 'fas fa-code text-primary' : 'fas fa-file text-secondary');
        $description = getDescription($file['name'], $file['is_image'], $file['is_page']);
        $status = ($file['is_page'] || $file['is_image']) ? "<span class='{$file['test_result']['class']} ms-2'>{$file['test_result']['status']}</span>" : '';
        $url = ($file['is_page'] || $file['is_image']) ? 'https://doublepower.org/' . str_replace(__DIR__ . '/', '', $file['path']) : '#';
        $output .= "<li class='tree-item'>
            {$indent}<i class='{$icon} me-2'></i><a href='{$url}' target='_blank'>" . htmlspecialchars($file['name']) . "</a>
            <span class='text-muted small'>{$description}</span>
            <span class='ms-2'>" . formatSize($file['size']) . "</span>
            <span class='ms-2'>[{$file['permissions']}]</span>
            <span class='ms-2'>{$file['modified']}</span>
            {$status}
        </li>";
    }
    return $output;
}

// دالة الوصف
function getDescription($name, $is_image = false, $is_page = false) {
    global $descriptions;
    if (isset($descriptions[$name])) return $descriptions[$name];
    if ($is_image) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return $descriptions["*.$ext"] ?? 'ملف صورة غير معروف.';
    }
    if ($is_page) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return $descriptions["*.$ext"] ?? 'ملف صفحة ويب.';
    }
    return 'لا يوجد وصف.';
}

// معالجة التصفية والبحث
$filter = isset($_POST['filter']) ? trim($_POST['filter']) : '';
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$filter_pattern = $filter ? "*.$filter" : '*';
$structure = scanDirectory(__DIR__, 0, $filter_pattern, $search);
$server_stats = getServerStats();

// احتساب الإجماليات
function countItems($dirs, $files) {
    $total_files = count($files);
    $total_dirs = count($dirs);
    foreach ($dirs as $dir) {
        $sub_counts = countItems($dir['subdirs'], $dir['subfiles']);
        $total_files += $sub_counts['files'];
        $total_dirs += $sub_counts['dirs'];
    }
    return ['files' => $total_files, 'dirs' => $total_dirs];
}
$counts = countItems($structure['dirs'], $structure['files']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم الموقع</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f0f0; }
        .navbar-brand { font-weight: bold; }
        .stats-card { background-color: #fff; border-radius: 8px; padding: 15px; margin-bottom: 10px; }
        .tree-list { list-style: none; padding-right: 20px; }
        .tree-item { padding: 5px 0; display: flex; align-items: center; }
        .toggle-icon { cursor: pointer; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/site_structure.php">لوحة تحكم Double Power</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="/site_structure.php">هيكلية الموقع</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/users.php">إدارة المستخدمين</a></li>
                <li class="nav-item"><a class="nav-link" href="/admin/logs.php">سجل الأنشطة</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <span class="text-light me-2"><?= htmlspecialchars($_SESSION['user_name'] ?? 'غير معروف') ?></span>
                <form action="/admin/admin_logout.php" method="post" class="d-flex">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['logout_token']) ?>">
                    <button type="submit" class="btn btn-outline-light">تسجيل الخروج</button>
                </form>
            </div>
        </div>
    </div>
</nav>
<div class="container py-5">
    <h2 class="mb-4">لوحة تحكم الموقع</h2>
    <div class="row mb-4">
        <div class="col-md-4"><div class="stats-card"><strong>المجلدات:</strong> <?= $counts['dirs'] ?></div></div>
        <div class="col-md-4"><div class="stats-card"><strong>الملفات:</strong> <?= $counts['files'] ?></div></div>
        <div class="col-md-4"><div class="stats-card"><strong>التخزين الكلي:</strong> <?= $server_stats['disk_total'] ?></div></div>
        <div class="col-md-4"><div class="stats-card"><strong>التخزين المستخدم:</strong> <?= $server_stats['disk_used'] ?></div></div>
        <div class="col-md-4"><div class="stats-card"><strong>التخزين المتبقي:</strong> <?= $server_stats['disk_free'] ?></div></div>
        <div class="col-md-4"><div class="stats-card"><strong>حد الذاكرة:</strong> <?= $server_stats['memory_limit'] ?></div></div>
    </div>
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">هيكلية الموقع</h4>
        </div>
        <div class="card-body">
            <div class="d-flex mb-3">
                <form class="filter-form me-2" method="post">
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="">جميع الملفات</option>
                        <option value="php" <?= $filter === 'php' ? 'selected' : '' ?>>PHP</option>
                        <option value="jpg" <?= $filter === 'jpg' ? 'selected' : '' ?>>JPG</option>
                        <option value="png" <?= $filter === 'png' ? 'selected' : '' ?>>PNG</option>
                        <option value="webp" <?= $filter === 'webp' ? 'selected' : '' ?>>WEBP</option>
                        <option value="html" <?= $filter === 'html' ? 'selected' : '' ?>>HTML</option>
                        <option value="css" <?= $filter === 'css' ? 'selected' : '' ?>>CSS</option>
                        <option value="js" <?= $filter === 'js' ? 'selected' : '' ?>>JS</option>
                    </select>
                </form>
                <form class="search-form flex-grow-1" method="post">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="ابحث باسم الملف/المجلد" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
            <?php if (isset($structure['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($structure['error']) ?></div>
            <?php elseif ($counts['dirs'] === 0 && $counts['files'] === 0): ?>
                <div class="alert alert-warning">لا توجد ملفات أو مجلدات لعرضها.</div>
            <?php else: ?>
                <ul class="tree-list">
                    <?= displayTree($structure['dirs'], $structure['files']) ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('.toggle-icon').click(function() {
        const $icon = $(this);
        const $parent = $(this).closest('.tree-item');
        const $list = $parent.find('> .tree-list');
        if ($list.hasClass('show')) {
            $list.removeClass('show');
            $icon.removeClass('fas fa-chevron-down').addClass('fas fa-chevron-right');
        } else {
            $list.addClass('show');
            $icon.removeClass('fas fa-chevron-right').addClass('fas fa-chevron-down');
        }
    });
});
</script>
</body>
</html>
