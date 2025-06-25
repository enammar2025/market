<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Category.php';

// إنشاء الاتصال بقاعدة البيانات
$database = new Database();
$db = $database->getConnection();

$category = new Category($db);

// الحصول على الفئات للتنقل
$categories_result = $category->read();
$categories = [];
while ($row = $categories_result->fetch(PDO::FETCH_ASSOC)) {
    $categories[] = $row;
}

// معالجة إرسال النموذج
$message_sent = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // التحقق من البيانات
    if (!empty($name) && !empty($email) && !empty($message)) {
        // هنا يمكن إضافة كود إرسال البريد الإلكتروني أو حفظ الرسالة في قاعدة البيانات
        $message_sent = true;
    } else {
        $error_message = 'يرجى ملء جميع الحقول المطلوبة';
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اتصل بنا - متجر الطاقة الكهربائية</title>
    <meta name="description" content="تواصل مع متجر الطاقة الكهربائية للاستفسارات والدعم الفني">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="اتصل بنا - متجر الطاقة الكهربائية">
    <meta property="og:description" content="تواصل مع متجر الطاقة الكهربائية للاستفسارات والدعم الفني">
    <meta property="og:type" content="website">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .contact-section {
            padding: 60px 0;
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .contact-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-bottom: 60px;
        }
        
        .contact-form {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .contact-form h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #2980b9;
        }
        
        .contact-info {
            background: #f8f9fa;
            padding: 40px;
            border-radius: 15px;
        }
        
        .contact-info h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-item i {
            color: #3498db;
            font-size: 24px;
            margin-left: 15px;
            margin-top: 5px;
        }
        
        .info-content h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.1rem;
        }
        
        .info-content p {
            color: #7f8c8d;
            line-height: 1.6;
            margin: 0;
        }
        
        .working-hours {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        
        .working-hours h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        
        .hours-list {
            list-style: none;
        }
        
        .hours-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .hours-list li:last-child {
            border-bottom: none;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d5f5d5;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        
        .alert-error {
            background: #f5d5d5;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        
        .map-section {
            margin-top: 60px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .map-section h2 {
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }
        
        .map-placeholder {
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .map-placeholder i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .contact-form,
            .contact-info {
                padding: 30px 20px;
            }
            
            .contact-header h1 {
                font-size: 2rem;
            }
            
            .info-item {
                flex-direction: column;
                text-align: center;
            }
            
            .info-item i {
                margin: 0 0 10px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
      <div class="container-fluid">

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
                    <li><a href="contact.php" class="active">اتصل بنا</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-header">
                <h1><i class="fas fa-phone"></i> اتصل بنا</h1>
                <p>نحن هنا لمساعدتك في جميع استفساراتك ومتطلباتك</p>
            </div>

            <div class="contact-content">
                <div class="contact-form">
                    <h2><i class="fas fa-envelope"></i> أرسل لنا رسالة</h2>
                    
                    <?php if ($message_sent): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="contact.php">
                        <div class="form-group">
                            <label for="name">الاسم الكامل *</label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">البريد الإلكتروني *</label>
                            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">رقم الهاتف</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">موضوع الرسالة</label>
                            <select id="subject" name="subject">
                                <option value="">اختر الموضوع</option>
                                <option value="استفسار عام">استفسار عام</option>
                                <option value="استفسار عن منتج">استفسار عن منتج</option>
                                <option value="طلب عرض سعر">طلب عرض سعر</option>
                                <option value="دعم فني">دعم فني</option>
                                <option value="شكوى">شكوى</option>
                                <option value="اقتراح">اقتراح</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">الرسالة *</label>
                            <textarea id="message" name="message" required placeholder="اكتب رسالتك هنا..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-paper-plane"></i> إرسال الرسالة
                        </button>
                    </form>
                </div>
                
                <div class="contact-info">
                    <h2><i class="fas fa-info-circle"></i> معلومات التواصل</h2>
                    
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div class="info-content">
                            <h3>الهاتف</h3>
                            <p>0506379661<br>للاستفسارات والطلبات</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="info-content">
                            <h3>البريد الإلكتروني</h3>
                            <p>info@powermarket.com<br>للاستفسارات العامة</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info-content">
                            <h3>العنوان</h3>
                            <p>الرياض، المملكة العربية السعودية<br>حي الملك فهد</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <i class="fas fa-globe"></i>
                        <div class="info-content">
                            <h3>الموقع الإلكتروني</h3>
                            <p>www.powermarket.com<br>متجر الطاقة الكهربائية</p>
                        </div>
                    </div>
                    
                    <div class="working-hours">
                        <h3><i class="fas fa-clock"></i> ساعات العمل</h3>
                        <ul class="hours-list">
                            <li><span>السبت - الخميس</span><span>8:00 ص - 10:00 م</span></li>
                            <li><span>الجمعة</span><span>2:00 م - 10:00 م</span></li>
                            <li><span>الدعم الفني</span><span>24/7</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="map-section">
                <h2><i class="fas fa-map"></i> موقعنا على الخريطة</h2>
                <div class="map-placeholder">
                    <i class="fas fa-map-marked-alt"></i>
                    <p>خريطة الموقع متاحة قريباً</p>
                    <p>الرياض، المملكة العربية السعودية</p>
                </div>
            </div>
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
</body>
</html>