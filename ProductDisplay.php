<?php

require_once __DIR__ . '/includes/config.php';



class ProductDisplay {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * عرض صفحة المنتج كاملة
     */
    public function showProductPage($productId) {
        // التحقق من أن معرف المنتج صحيح
        if (!is_numeric($productId) || $productId <= 0) {
            return $this->showError("معرف المنتج غير صحيح");
        }
        
        // الحصول على بيانات المنتج
        $product = $this->getProductDetails($productId);
        
        if (!$product) {
            return $this->showError("المنتج غير موجود");
        }
        
        // الحصول على الصور الإضافية
        $additionalImages = $this->getProductImages($productId);
        
        // زيادة عدد المشاهدات
        $this->incrementProductViews($productId);
        
        // عرض صفحة المنتج
        return $this->renderProductPage($product, $additionalImages);
    }
    
    /**
     * الحصول على تفاصيل المنتج من قاعدة البيانات
     */
    private function getProductDetails($productId) {
        $stmt = $this->conn->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ? AND p.status = 1
        ");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * الحصول على الصور الإضافية للمنتج
     */
    private function getProductImages($productId) {
        $stmt = $this->conn->prepare("
            SELECT image 
            FROM product_images 
            WHERE product_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row['image'];
        }
        
        return $images;
    }
    
    /**
     * زيادة عدد مشاهدات المنتج
     */
    private function incrementProductViews($productId) {
        // هنا يمكنك إضافة كود لتسجيل المشاهدة في جدول product_views
        // كما في قاعدة البيانات الأصلية
    }
    
    /**
     * عرض صفحة المنتج كاملة
     */
    private function renderProductPage($product, $additionalImages) {
        ob_start(); // بدء تخزين المخرجات
        
        // الصورة الرئيسية
        $mainImage = !empty($product['image']) ? UPLOAD_DIR . $product['image'] : DEFAULT_PRODUCT_IMAGE;
        
        // معالجة السعر
        $priceHTML = $this->formatPrice($product['price'], $product['compare_price']);
        
        // بداية HTML
        ?>
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($product['name']) ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="assets/css/product.css">
        </head>
        <body>
            <div class="container py-5">
                <div class="product-details">
                    <div class="row">
                        <!-- قسم الصور -->
                        <div class="col-md-6">
                            <?= $this->renderImageGallery($mainImage, $additionalImages) ?>
                        </div>
                        
                        <!-- قسم التفاصيل -->
                        <div class="col-md-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                                    <li class="breadcrumb-item"><a href="category.php?id=<?= $product['category_id'] ?>"><?= htmlspecialchars($product['category_name'] ?? 'المنتجات') ?></a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['name']) ?></li>
                                </ol>
                            </nav>
                            
                            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                            
                            <div class="product-meta mb-3">
                                <?php if (!empty($product['sku'])): ?>
                                    <span class="badge bg-secondary">رقم المنتج: <?= htmlspecialchars($product['sku']) ?></span>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['category_name'])): ?>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($product['category_name']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-price mb-4">
                                <?= $priceHTML ?>
                            </div>
                            
                            <div class="product-description mb-4">
                                <?= nl2br(htmlspecialchars($product['description'])) ?>
                            </div>
                            
                            <div class="product-actions">
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <form class="add-to-cart-form">
                                        <div class="input-group mb-3" style="max-width: 200px;">
                                            <button class="btn btn-outline-secondary" type="button" id="decrement">-</button>
                                            <input type="number" class="form-control text-center" value="1" min="1" max="<?= $product['stock_quantity'] ?>" id="quantity">
                                            <button class="btn btn-outline-secondary" type="button" id="increment">+</button>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-cart-plus"></i> أضف إلى السلة
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-warning">هذا المنتج غير متوفر حالياً</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- قسم المنتجات ذات الصلة -->
                    <div class="related-products mt-5">
                        <h3 class="section-title">منتجات ذات صلة</h3>
                        <div class="row">
                            <?= $this->renderRelatedProducts($product['category_id'], $product['id']) ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
            <script src="assets/js/product.js"></script>
        </body>
        </html>
        <?php
        
        return ob_get_clean(); // إرجاع المحتوى المخزن
    }
    
    /**
     * عرض معرض الصور
     */
    private function renderImageGallery($mainImage, $additionalImages) {
        ob_start();
        ?>
        <div class="product-gallery">
            <div class="main-image mb-3">
                <img src="<?= $mainImage ?>" alt="صورة المنتج الرئيسية" class="img-fluid rounded" id="main-product-image">
            </div>
            
            <?php if (!empty($additionalImages)): ?>
                <div class="thumbnails">
                    <div class="row g-2">
                        <div class="col-3">
                            <img src="<?= $mainImage ?>" class="img-thumbnail thumbnail-img active" onclick="changeMainImage(this)">
                        </div>
                        <?php foreach ($additionalImages as $image): ?>
                            <div class="col-3">
                                <img src="<?= UPLOAD_DIR . $image ?>" class="img-thumbnail thumbnail-img" onclick="changeMainImage(this)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * تنسيق السعر مع التخفيضات
     */
    private function formatPrice($price, $comparePrice) {
        $formattedPrice = number_format($price, 2) . ' ر.س';
        
        if ($comparePrice && $comparePrice > $price) {
            $discountPercent = round((($comparePrice - $price) / $comparePrice) * 100);
            return '
                <span class="original-price text-muted text-decoration-line-through me-2">'.number_format($comparePrice, 2).' ر.س</span>
                <span class="current-price fw-bold">'.$formattedPrice.'</span>
                <span class="badge bg-danger ms-2">وفر '.$discountPercent.'%</span>
            ';
        }
        
        return '<span class="current-price fw-bold">'.$formattedPrice.'</span>';
    }
    
    /**
     * عرض المنتجات ذات الصلة
     */
    private function renderRelatedProducts($categoryId, $excludeProductId) {
        $stmt = $this->conn->prepare("
            SELECT id, name, price, image 
            FROM products 
            WHERE category_id = ? AND id != ? AND status = 1 
            ORDER BY RAND() 
            LIMIT 4
        ");
        $stmt->bind_param("ii", $categoryId, $excludeProductId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $html = '';
        while ($product = $result->fetch_assoc()) {
            $image = !empty($product['image']) ? UPLOAD_DIR . $product['image'] : DEFAULT_PRODUCT_IMAGE;
            $price = number_format($product['price'], 2) . ' ر.س';
            
            $html .= '
            <div class="col-md-3 col-6 mb-4">
                <div class="card h-100">
                    <img src="'.$image.'" class="card-img-top" alt="'.htmlspecialchars($product['name']).'">
                    <div class="card-body">
                        <h5 class="card-title"><a href="product.php?id='.$product['id'].'">'.htmlspecialchars($product['name']).'</a></h5>
                        <p class="card-text text-primary fw-bold">'.$price.'</p>
                    </div>
                </div>
            </div>
            ';
        }
        
        if (empty($html)) {
            $html = '<div class="col-12"><p>لا توجد منتجات ذات صلة</p></div>';
        }
        
        return $html;
    }
    
    /**
     * عرض رسالة خطأ
     */
    private function showError($message) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="ar" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>خطأ</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body>
            <div class="container py-5">
                <div class="alert alert-danger">
                    <?= htmlspecialchars($message) ?>
                </div>
                <a href="index.php" class="btn btn-primary">العودة إلى الصفحة الرئيسية</a>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
?>