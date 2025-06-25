<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Cart.php';
require_once 'classes/Category.php';

// إنشاء الاتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

$cart = new Cart($db);
$category = new Category($db);

// الحصول على معرف الجلسة
$session_id = session_id();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// الحصول على معرف السلة
$cart_id = $cart->getOrCreateCart($user_id, $session_id);

// الحصول على عناصر السلة
$cart_items_result = $cart->getItems($cart_id);
$cart_items = [];
while ($row = $cart_items_result->fetch(PDO::FETCH_ASSOC)) {
    $cart_items[] = $row;
}

$cart_total = $cart->getCartTotal($cart_id);
$cart_count = $cart->getCartItemsCount($cart_id);

// الحصول على الفئات للتنقل
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سلة التسوق - متجر الطاقة الكهربائية</title>
    <meta name="description" content="مراجعة منتجات سلة التسوق في متجر الطاقة الكهربائية">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .cart-section {
            padding: 40px 0;
            min-height: 70vh;
        }
        
        .cart-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .cart-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .cart-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .cart-items {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cart-summary {
            background: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto auto auto;
            gap: 20px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-image img {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .item-details h3 {
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .item-details .item-price {
            color: #27ae60;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .quantity-controls button {
            background: #f8f9fa;
            border: none;
            width: 35px;
            height: 35px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .quantity-controls button:hover {
            background: #e9ecef;
        }
        
        .quantity-controls input {
            width: 50px;
            height: 35px;
            text-align: center;
            border: none;
            font-size: 14px;
        }
        
        .item-total {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .remove-item {
            color: #e74c3c;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            transition: color 0.3s;
        }
        
        .remove-item:hover {
            color: #c0392b;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-cart h2 {
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
        }
        
        .summary-row.total {
            border-top: 2px solid #eee;
            padding-top: 15px;
            font-weight: bold;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        
        .checkout-section {
            margin-top: 30px;
        }
        
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .checkout-btn:hover {
            background: #219a52;
        }
        
        .continue-shopping {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .continue-shopping:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .cart-item {
                grid-template-columns: 80px 1fr;
                gap: 15px;
            }
            
            .item-controls {
                grid-column: 1 / -1;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 10px;
            }
            
            .quantity-controls {
                margin-right: auto;
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
                        <span class="cart-count" id="cart-count"><?php echo $cart_count; ?></span>
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

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <div class="cart-header">
                <h1><i class="fas fa-shopping-cart"></i> سلة التسوق</h1>
                <p>مراجعة المنتجات المختارة قبل إتمام الطلب</p>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>سلة التسوق فارغة</h2>
                    <p>لم تقم بإضافة أي منتجات إلى سلة التسوق بعد</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-arrow-right"></i> تصفح المنتجات
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items">
                        <h3 style="margin-bottom: 25px; color: #2c3e50;">
                            <i class="fas fa-list"></i> المنتجات (<?php echo count($cart_items); ?>)
                        </h3>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                                <div class="item-image">
                                    <img src="<?php echo $item['image'] ?: 'assets/images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="item-price"><?php echo number_format($item['price'], 2); ?> ر.س</div>
                                </div>
                                
                                <div class="quantity-controls">
                                    <button onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                    <input type="number" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock_quantity']; ?>"
                                           onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                    <button onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                </div>
                                
                                <div class="item-total">
                                    <?php echo number_format($item['price'] * $item['quantity'], 2); ?> ر.س
                                </div>
                                
                                <div class="remove-item" onclick="removeItem(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="margin-top: 30px; text-align: center;">
                            <button onclick="clearCart()" class="btn btn-secondary">
                                <i class="fas fa-trash"></i> إفراغ السلة
                            </button>
                        </div>
                    </div>
                    
                    <div class="cart-summary">
                        <h3 style="margin-bottom: 25px; color: #2c3e50;">
                            <i class="fas fa-calculator"></i> ملخص الطلب
                        </h3>
                        
                        <div class="summary-row">
                            <span>المجموع الفرعي:</span>
                            <span id="subtotal"><?php echo number_format($cart_total, 2); ?> ر.س</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>الشحن:</span>
                            <span>مجاني</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>الضريبة (15%):</span>
                            <span id="tax"><?php echo number_format($cart_total * 0.15, 2); ?> ر.س</span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>المجموع الكلي:</span>
                            <span id="total"><?php echo number_format($cart_total * 1.15, 2); ?> ر.س</span>
                        </div>
                        
                        <div class="checkout-section">
                            <a href="checkout.php" class="checkout-btn">
                                <i class="fas fa-credit-card"></i> إتمام الطلب
                            </a>
                            
                            <a href="index.php" class="continue-shopping">
                                <i class="fas fa-arrow-right"></i> متابعة التسوق
                            </a>
                        </div>
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
        function updateQuantity(itemId, quantity) {
            if (quantity < 1) {
                removeItem(itemId);
                return;
            }
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    item_id: itemId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartDisplay(data);
                    showNotification('تم تحديث الكمية');
                } else {
                    showNotification('حدث خطأ في تحديث الكمية', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('حدث خطأ في تحديث الكمية', 'error');
            });
        }
        
        function removeItem(itemId) {
            if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                return;
            }
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove',
                    item_id: itemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
                    if (itemElement) {
                        itemElement.remove();
                    }
                    updateCartDisplay(data);
                    showNotification('تم حذف المنتج من السلة');
                    
                    // Check if cart is empty
                    const remainingItems = document.querySelectorAll('.cart-item').length;
                    if (remainingItems === 0) {
                        location.reload();
                    }
                } else {
                    showNotification('حدث خطأ في حذف المنتج', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('حدث خطأ في حذف المنتج', 'error');
            });
        }
        
        function clearCart() {
            if (!confirm('هل أنت متأكد من إفراغ السلة بالكامل؟')) {
                return;
            }
            
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'clear'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification('حدث خطأ في إفراغ السلة', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('حدث خطأ في إفراغ السلة', 'error');
            });
        }
        
        function updateCartDisplay(data) {
            // Update cart count
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = data.count || 0;
            }
            
            // Update totals
            const subtotal = parseFloat(data.total || 0);
            const tax = subtotal * 0.15;
            const total = subtotal + tax;
            
            const subtotalElement = document.getElementById('subtotal');
            const taxElement = document.getElementById('tax');
            const totalElement = document.getElementById('total');
            
            if (subtotalElement) subtotalElement.textContent = subtotal.toFixed(2) + ' ر.س';
            if (taxElement) taxElement.textContent = tax.toFixed(2) + ' ر.س';
            if (totalElement) totalElement.textContent = total.toFixed(2) + ' ر.س';
        }
        
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = 'notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                transform: translateX(400px);
                transition: transform 0.3s ease;
            `;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>