<?php
require_once 'ProductDisplay.php';

// الحصول على معرف المنتج من URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// إنشاء كائن لعرض المنتجات
$productDisplay = new ProductDisplay($conn);

// عرض صفحة المنتج
echo $productDisplay->showProductPage($productId);
?>