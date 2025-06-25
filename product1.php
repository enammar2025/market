<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';

// إنشاء الاتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

// إنشاء كائنات الفئات والمنتجات
$product = new Product($db);
$category = new Category($db);

// الحصول على slug المنتج
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// الحصول على بيانات المنتج
$product_data = $product->readBySlug($slug);

if (!$product_data) {
    header('Location: index.php');
    exit;
}

// الحصول على الفئات
$categories_result = $category->read();
$categories = [];
while ($row = $categories_result->fetch(PDO::FETCH_ASSOC)) {
    $categories[] = $row;
}

// الحصول على منتجات مشابهة
$related_products = [];
if ($product_data['category_id']) {
    $related_result = $product->read(1, 4, $product_data['category_id']);
    while ($row = $related_result->fetch(PDO::FETCH_ASSOC)) {
        if ($row['id'] != $product_data['id']) {
            $related_products[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product_data['name']); ?> - متجر الطاقة الكهربائية</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($product_data['description'], 0, 160)); ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($product_data['name']); ?> - متجر الطاقة الكهربائية">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($product_data['description'], 0, 160)); ?>">
    <meta property="og:type" content="product">
    <meta property="og:url" content="<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="<?php echo $product_data['image'] ?: 'assets/images/placeholder.jpg'; ?>">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .product-detail {
            padding: 40px 0;
        }
        
        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }
        
        .product-gallery {
            position: relative;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .product-details h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.3;
        }
        
        .product-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            color: #7f8c8d;
        }
        
        .product-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .product-price {
            margin-bottom: 25px;
        }
        
        .product-price .current-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .product-price .old-price {
            font-size: 1.8rem;
            color: #95a5a6;
            text-decoration: line-through;
            margin-right: 15px;
        }
        
        .discount-percent {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .stock-status {
            margin-bottom: 25px;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .in-stock {
            background: #d5f5d5;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        
        .out-of-stock {
            background: #f5d5d5;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        
        .product-description {
            line-height: 1.8;
            color: #555;
            margin-bottom: 30px;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .quantity-controls button {
            background: #f8f9fa;
            border: none;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 18px;
            color: #333;
            transition: background-color 0.3s;
        }
        
        .quantity-controls button:hover {
            background: #e9ecef;
        }
        
        .quantity-controls input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: none;
            font-size: 16px;
            font-weight: 500;
        }
        
        .add-to-cart-section {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .add-to-cart-section .btn {
            flex: 1;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .product-features {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .features-list {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }
        
        .features-list i {
            color: #27ae60;
            font-size: 18px;
        }
        
        .related-products {
            margin-top: 60px;
        }
        
        .related-products h3 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .product-main {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .main-image {
                height: 300px;
            }
            
            .product-details h1 {
                font-size: 1.8rem;
            }
            
            .product-price .current-price {
                font-size: 2rem;
            }
            
            .add-to-cart-section {
                flex-direction: column;
            }
            
            .features-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-top">
                <div class="contact-info">
                    <span><i class="fas fa-phone"></i> 0506379661</span>
                    <span><i class="fas fa-envelope"></i> info@powermarket.com</span>
                </div>
                <div class="header-actions">
                    <a href="login.php">تسجيل الدخول</a>
                    <a href="register.php">إنشاء حساب</a>
                </div>
            </div>
            
            <div class="header-main">
                <div class="logo">
                    <a href="index.php"><h1><i class="fas fa-bolt"></i> متجر الطاقة</h1></a>
                </div>
                
                <div class="search-bar">
                    <form method="GET" action="index.php">
                        <input type="text" name="search" placeholder="ابحث عن المنتجات...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="cart-icon">
                    <a href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="cart-count">0</span>
                    </a>
                </div>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php">الرئيسية</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="index.php?category=<?php echo $cat['id']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li><a href="contact.php">اتصل بنا</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Product Detail -->
    <section class="product-detail">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb" style="margin-bottom: 30px; color: #7f8c8d;">
                <a href="index.php" style="color: #3498db; text-decoration: none;">الرئيسية</a>
                <span style="margin: 0 10px;">/</span>
                <?php if ($product_data['category_name']): ?>
                    <a href="index.php?category=<?php echo $product_data['category_id']; ?>" style="color: #3498db; text-decoration: none;">
                        <?php echo htmlspecialchars($product_data['category_name']); ?>
                    </a>
                    <span style="margin: 0 10px;">/</span>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($product_data['name']); ?></span>
            </nav>

            <div class="product-main">
                <div class="product-gallery">
                   <?php
$imageFile = 'assets/images/products/' . basename($product_data['image']);
if (empty($product_data['image']) || !file_exists($imageFile)) {
    $imageFile = 'assets/images/placeholder.jpg';
}
?>
<img src="<?php echo $imageFile; ?>"
     alt="<?php echo htmlspecialchars($product_data['name']); ?>"
     class="main-image">

                </div>
                
                <div class="product-details">
                    <h1><?php echo htmlspecialchars($product_data['name']); ?></h1>
                    
                    <div class="product-meta">
                        <span><i class="fas fa-barcode"></i> <?php echo htmlspecialchars($product_data['sku']); ?></span>
                        <?php if ($product_data['category_name']): ?>
                            <span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($product_data['category_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-price">
                        <?php if ($product_data['compare_price'] && $product_data['compare_price'] > $product_data['price']): ?>
                            <span class="discount-percent">
                                <?php echo round((($product_data['compare_price'] - $product_data['price']) / $product_data['compare_price']) * 100); ?>% خصم
                            </span>
                            <span class="old-price"><?php echo number_format($product_data['compare_price'], 2); ?> ر.س</span>
                        <?php endif; ?>
                        <span class="current-price"><?php echo number_format($product_data['price'], 2); ?> ر.س</span>
                    </div>
                    
                    <div class="stock-status <?php echo $product_data['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php if ($product_data['stock_quantity'] > 0): ?>
                            <i class="fas fa-check-circle"></i> متوفر في المخزون (<?php echo $product_data['stock_quantity']; ?> قطعة)
                        <?php else: ?>
                            <i class="fas fa-times-circle"></i> غير متوفر حالياً
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($product_data['description'])): ?>
                        <div class="product-description">
                            <p><?php echo nl2br(htmlspecialchars($product_data['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($product_data['stock_quantity'] > 0): ?>
                        <div class="quantity-selector">
                            <label>الكمية:</label>
                            <div class="quantity-controls">
                                <button type="button" onclick="decreaseQuantity()">-</button>
                                <input type="number" id="quantity" value="1" min="1" max="<?php echo $product_data['stock_quantity']; ?>">
                                <button type="button" onclick="increaseQuantity()">+</button>
                            </div>
                        </div>
                        
                        <div class="add-to-cart-section">
                            <button class="btn btn-primary add-to-cart" 
                                    data-id="<?php echo $product_data['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($product_data['name']); ?>"
                                    data-price="<?php echo $product_data['price']; ?>"
                                    onclick="addToCartWithQuantity()">
                                <i class="fas fa-cart-plus"></i> إضافة إلى السلة
                            </button>
                            <a href="contact.php" class="btn btn-secondary">
                                <i class="fas fa-phone"></i> استفسار
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-features">
                        <h4 style="margin-bottom: 15px; color: #2c3e50;"><i class="fas fa-star"></i> مميزات المنتج</h4>
                        <ul class="features-list">
                            <li><i class="fas fa-shield-alt"></i> ضمان الشركة المصنعة</li>
                            <li><i class="fas fa-shipping-fast"></i> توصيل سريع</li>
                            <li><i class="fas fa-tools"></i> دعم فني متخصص</li>
                            <li><i class="fas fa-certificate"></i> جودة عالية مضمونة</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
                <div class="related-products">
                    <h3>منتجات مشابهة</h3>
                    <div class="products-grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                        <?php foreach ($related_products as $related): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo $related['image'] ?: 'assets/images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>">
                                    <?php if ($related['compare_price'] && $related['compare_price'] > $related['price']): ?>
                                        <span class="discount-badge">
                                            <?php echo round((($related['compare_price'] - $related['price']) / $related['compare_price']) * 100); ?>% خصم
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($related['name']); ?></h3>
                                    <p class="product-description"><?php echo htmlspecialchars(substr($related['description'], 0, 80)) . '...'; ?></p>
                                    
                                    <div class="product-price">
                                        <span class="current-price"><?php echo number_format($related['price'], 2); ?> ر.س</span>
                                        <?php if ($related['compare_price'] && $related['compare_price'] > $related['price']): ?>
                                            <span class="old-price"><?php echo number_format($related['compare_price'], 2); ?> ر.س</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="product.php?slug=<?php echo $related['slug']; ?>" class="btn btn-secondary">
                                            <i class="fas fa-eye"></i> عرض
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>متجر الطاقة الكهربائية</h3>
                    <p>متخصصون في توفير أفضل المولدات الكهربائية ومعدات الطاقة بأعلى جودة وأفضل الأسعار</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>روابط سريعة</h4>
                    <ul>
                        <li><a href="index.php">الرئيسية</a></li>
                        <li><a href="about.php">من نحن</a></li>
                        <li><a href="contact.php">اتصل بنا</a></li>
                        <li><a href="privacy.php">سياسة الخصوصية</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>الفئات</h4>
                    <ul>
                        <?php foreach ($categories as $cat): ?>
                            <li><a href="index.php?category=<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>تواصل معنا</h4>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>0506379661</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>info@powermarket.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>الرياض، المملكة العربية السعودية</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 متجر الطاقة الكهربائية. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Chatbot -->
    <div class="chatbot-container" id="chatbot">
        <div class="chatbot-header">
            <span>دعم العملاء</span>
            <button id="chatbot-close">&times;</button>
        </div>
        <div class="chatbot-messages" id="chatbot-messages">
            <div class="bot-message">
                مرحباً! كيف يمكنني مساعدتك اليوم؟
            </div>
        </div>
        <div class="chatbot-input">
            <input type="text" id="chatbot-input" placeholder="اكتب رسالتك هنا...">
            <button id="chatbot-send"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>

    <button class="chatbot-toggle" id="chatbot-toggle">
        <i class="fas fa-comments"></i>
    </button>

    <script src="assets/js/script.js"></script>
    <script>
        function increaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const max = parseInt(quantityInput.getAttribute('max'));
            const current = parseInt(quantityInput.value);
            
            if (current < max) {
                quantityInput.value = current + 1;
            }
        }
        
        function decreaseQuantity() {
            const quantityInput = document.getElementById('quantity');
            const current = parseInt(quantityInput.value);
            
            if (current > 1) {
                quantityInput.value = current - 1;
            }
        }
        
        function addToCartWithQuantity() {
            const button = document.querySelector('.add-to-cart');
            const quantity = parseInt(document.getElementById('quantity').value);
            
            // Update button data with quantity
            button.dataset.quantity = quantity;
            
            // Get existing cart and add item
            const cart = new Cart();
            const productId = button.dataset.id;
            const productName = button.dataset.name;
            const productPrice = parseFloat(button.dataset.price);
            
            // Add items based on quantity
            for (let i = 0; i < quantity; i++) {
                cart.addToCart(button);
            }
        }
    </script>
</body>
</html>