<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = '';
$success = '';

if ($_POST) {
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $user_data = $user->login($email, $password);
        
        if ($user_data) {
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['role'] = $user_data['role'];
            $_SESSION['first_name'] = $user_data['first_name'];
            
            if ($user_data['role'] == 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة';
        }
    }
    
    if (isset($_POST['register'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'] ?? '';
        
        if ($password !== $confirm_password) {
            $error = 'كلمات المرور غير متطابقة';
        } else if ($user->emailExists($email)) {
            $error = 'البريد الإلكتروني مسجل مسبقاً';
        } else if ($user->usernameExists($username)) {
            $error = 'اسم المستخدم موجود مسبقاً';
        } else {
            $user_id = $user->register($username, $email, $password, $first_name, $last_name, $phone);
            if ($user_id) {
                $success = 'تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن';
            } else {
                $error = 'حدث خطأ في إنشاء الحساب';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - متجر الطاقة الكهربائية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .auth-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        .auth-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .auth-tab.active {
            border-bottom-color: #2980b9;
            color: #2980b9;
            font-weight: bold;
        }
        .auth-form {
            display: none;
        }
        .auth-form.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2980b9;
        }
        .auth-btn {
            width: 100%;
            padding: 15px;
            background: #2980b9;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .auth-btn:hover {
            background: #1e6091;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="index.php">متجر الطاقة الكهربائية</a>
            </div>
            <div class="navbar-menu">
                <a href="index.php">الرئيسية</a>
                <a href="contact.php">اتصل بنا</a>
            </div>
        </div>
    </nav>

    <div class="auth-container">
        <div class="auth-tabs">
            <div class="auth-tab active" data-tab="login">تسجيل الدخول</div>
            <div class="auth-tab" data-tab="register">إنشاء حساب</div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form class="auth-form active" id="login-form" method="POST">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="auth-btn">تسجيل الدخول</button>
        </form>

        <form class="auth-form" id="register-form" method="POST">
            <div class="form-group">
                <label for="reg_username">اسم المستخدم</label>
                <input type="text" id="reg_username" name="username" required>
            </div>
            <div class="form-group">
                <label for="reg_first_name">الاسم الأول</label>
                <input type="text" id="reg_first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="reg_last_name">الاسم الأخير</label>
                <input type="text" id="reg_last_name" name="last_name" required>
            </div>
            <div class="form-group">
                <label for="reg_email">البريد الإلكتروني</label>
                <input type="email" id="reg_email" name="email" required>
            </div>
            <div class="form-group">
                <label for="reg_phone">رقم الهاتف</label>
                <input type="tel" id="reg_phone" name="phone">
            </div>
            <div class="form-group">
                <label for="reg_password">كلمة المرور</label>
                <input type="password" id="reg_password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">تأكيد كلمة المرور</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" name="register" class="auth-btn">إنشاء حساب</button>
        </form>
    </div>

    <script>
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Remove active class from all tabs and forms
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding form
                this.classList.add('active');
                document.getElementById(tabName + '-form').classList.add('active');
            });
        });
    </script>
</body>
</html>