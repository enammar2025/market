<?php
// إعدادات عرض الأخطاء
ini_set('display_errors', 1);
error_reporting(E_ALL);

// الاتصال بقاعدة البيانات
require_once 'config/database.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// إنشاء كائنات الفئات والمنتجات
$product = new Product($conn);
$category = new Category($conn);

// معالجة الفلاتر والبحث
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

$limit = 12;
$products_result = $product->read($page, $limit, $category_id, $search);
$total_products = $product->count($category_id, $search);
$total_pages = ceil($total_products / $limit);

// جلب التصنيفات
$categories_result = $category->read();
$categories = [];
while ($row = $categories_result->fetch(PDO::FETCH_ASSOC)) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>PowerHub - المتجر الإلكتروني</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: 'Tajawal', sans-serif; background-color: #f8f9fa; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .card-img-top { height: 200px; object-fit: cover; }
        .cart-icon { position: fixed; bottom: 20px; left: 20px; background-color: #007bff; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; cursor: pointer; z-index: 1000; transition: all 0.3s ease; }
        .cart-icon:hover { transform: scale(1.1); }
        .cart-count { position: absolute; top: -5px; right: -5px; background: red; color: white; padding: 2px 6px; border-radius: 50%; font-size: 12px; display: none; }
        .sidebar { margin-bottom: 20px; }
        @media (max-width: 768px) { .product-card img { height: 150px; } }
    </style>
</head>
<body>
<h1 class="text-center my-4">منتجات PowerHub</h1>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card sidebar shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">التصنيفات</h5>
                </div>
                <ul class="list-group list-group-flush" id="category-list">
                    <li class="list-group-item active" data-cat-id="all">الكل</li>
                    <?php foreach ($categories as $cat): ?>
                        <li class="list-group-item" data-cat-id="<?= htmlspecialchars($cat['id']) ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن منتج...">
            </div>
            <button id="resetFilters" class="btn btn-outline-danger w-100">إعادة التعيين</button>
        </div>
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>عدد المنتجات المعروضة: <span id="product-count"><?= $total_products ?></span></div>
            </div>
            <div class="row g-4" id="products-container">
                <?php while ($row = $products_result->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="col-md-4 col-sm-6 product-item"
                         data-cat-id="<?= htmlspecialchars($row['category_id']) ?>"
                         data-name="<?= mb_strtolower(htmlspecialchars($row['name'])) ?>"
                         data-desc="<?= mb_strtolower(htmlspecialchars($row['description'])) ?>">
                        <div class="card product-card h-100 shadow-sm">
                            <?php
                            $imageFile = '/assets/images/products/default.jpg';
                            if (!empty($row['image'])) {
                                $imageFile = $row['image']; // تم جلبه كمسار كامل من قاعدة البيانات
                            }
                            ?>
                            <img src="<?= htmlspecialchars($imageFile) ?>"
                                 class="card-img-top"
                                 onerror="this.onerror=null; this.src='/assets/images/products/default.jpg';"
                                 alt="<?= htmlspecialchars($row['name'] ?? 'منتج') ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                                <p class="text-success fw-bold"><?= number_format($row['price'], 2) ?> ريال</p>
                                <p class="<?= $row['stock_quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $row['stock_quantity'] > 0 ? 'متوفر' : 'غير متوفر' ?>
                                </p>
                                <button class="btn btn-outline-primary add-to-cart-btn mt-auto"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-price="<?= $row['price'] ?>"
                                        data-image="<?= htmlspecialchars($imageFile) ?>"
                                        data-stock="<?= $row['stock_quantity'] ?>"
                                        <?= $row['stock_quantity'] <= 0 ? 'disabled' : '' ?>
                                >
                                    أضف إلى السلة
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <nav aria-label="Pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(['category' => $category_id, 'search' => $search]) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>
<div class="cart-icon" id="cartIcon" title="سلة المشتريات">
    🛒<span class="cart-count" id="cartCount" style="display:none;">0</span>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// يمكنك إضافة كود JavaScript الخاص بالسلة والفلترة هنا
</script>
</body>
</html>
