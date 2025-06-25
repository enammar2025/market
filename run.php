<?php
// تشغيل الخادم مع إعدادات محسنة
echo "بدء تشغيل متجر الطاقة الكهربائية...\n";

// التحقق من وجود قاعدة البيانات
if (!file_exists('powermarket.db')) {
    echo "إنشاء قاعدة البيانات...\n";
    require_once 'init_db.php';
}

echo "الخادم يعمل على: http://0.0.0.0:8000\n";
echo "اضغط Ctrl+C للإيقاف\n\n";

// تشغيل الخادم
$command = 'php -S 0.0.0.0:8000 -t .';
passthru($command);
?>