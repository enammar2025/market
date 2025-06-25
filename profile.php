<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';
require_once 'classes/Order.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$order = new Order($db);

$user_data = $user->getUserById($_SESSION['user_id']);
$user_orders = $order->getUserOrders($_SESSION['user_id']);

$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['update_profile'])) {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        
        if ($user->updateProfile($_SESSION['user_id'], $first_name, $last_name, $phone, $address)) {
            $message = 'تم تحديث الملف الشخصي بنجاح';
            $message_type = 'success';
            $user_data = $user->getUserById($_SESSION['user_id']); // تحديث البيانات
        } else {
            $message = 'حدث خطأ في تحديث الملف الشخصي';
            $message_type = 'error';
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $message = 'كلمات المرور الجديدة غير متطابقة';
            $message_type = 'error';
        } else if ($user->changePassword($_SESSION['user_id'], $current_password, $new_password)) {
            $message = 'تم تغيير كلمة المرور بنجاح';
            $message_type = 'success';
        } else {
            $message = 'كلمة المرور الحالية غير صحيحة';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - متجر الطاقة الكهربائية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
        }
        .profile-tabs {
            display: flex;
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .profile-tab.active {
            border-bottom-color: #3498db;
            background: #f8f9fa;
        }
        .tab-content {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .tab-content.active {
            display: block;
        }
        .profile-info {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            background: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            margin: 0 auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
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
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        .orders-table th,
        .orders-table td {
            text-align: right;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .orders-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d4edda; color: #155724; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="profile-container">
        <h1>الملف الشخصي</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="profile-tabs">
            <div class="profile-tab active" data-tab="info">معلومات الحساب</div>
            <div class="profile-tab" data-tab="orders">طلباتي</div>
            <div class="profile-tab" data-tab="password">تغيير كلمة المرور</div>
        </div>

        <!-- معلومات الحساب -->
        <div class="tab-content active" id="info-tab">
            <div class="profile-info">
                <div>
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_data['first_name'], 0, 1)); ?>
                    </div>
                </div>
                <div>
                    <h2><?php echo $user_data['first_name'] . ' ' . $user_data['last_name']; ?></h2>
                    <p><i class="fas fa-envelope"></i> <?php echo $user_data['email']; ?></p>
                    <p><i class="fas fa-user"></i> <?php echo $user_data['username']; ?></p>
                    <p><i class="fas fa-calendar"></i> عضو منذ <?php echo date('Y-m-d', strtotime($user_data['created_at'])); ?></p>
                </div>
            </div>

            <div class="user-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($user_orders); ?></div>
                    <div class="stat-label">إجمالي الطلبات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php 
                        $total_spent = array_sum(array_column($user_orders, 'total'));
                        echo number_format($total_spent, 2);
                    ?> ر.س</div>
                    <div class="stat-label">إجمالي المشتريات</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php 
                        $completed_orders = array_filter($user_orders, function($order) {
                            return $order['status'] == 'delivered';
                        });
                        echo count($completed_orders);
                    ?></div>
                    <div class="stat-label">الطلبات المكتملة</div>
                </div>
            </div>

            <form method="POST">
                <h3>تحديث المعلومات الشخصية</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">الاسم الأول</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo $user_data['first_name']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">الاسم الأخير</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo $user_data['last_name']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">رقم الهاتف</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo $user_data['phone']; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="address">العنوان</label>
                    <textarea id="address" name="address"><?php echo $user_data['address']; ?></textarea>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">
                    تحديث المعلومات
                </button>
            </form>
        </div>

        <!-- طلباتي -->
        <div class="tab-content" id="orders-tab">
            <h3>سجل الطلبات</h3>
            <?php if (empty($user_orders)): ?>
                <p>لا توجد طلبات سابقة</p>
            <?php else: ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>طريقة الدفع</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                            <td><?php echo number_format($order['total'], 2); ?> ر.س</td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php
                                    $statuses = [
                                        'pending' => 'في الانتظار',
                                        'processing' => 'قيد المعالجة',
                                        'shipped' => 'تم الشحن',
                                        'delivered' => 'تم التسليم'
                                    ];
                                    echo $statuses[$order['status']] ?? $order['status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $payment_methods = [
                                    'local_wallet' => 'المحفظة الرقمية',
                                    'credit_card' => 'البطاقة الائتمانية',
                                    'bank_transfer' => 'التحويل البنكي',
                                    'cash_on_delivery' => 'الدفع عند التسليم'
                                ];
                                echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                            </td>
                            <td>
                                <a href="order-details.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-small">عرض التفاصيل</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- تغيير كلمة المرور -->
        <div class="tab-content" id="password-tab">
            <h3>تغيير كلمة المرور</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">كلمة المرور الحالية</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">كلمة المرور الجديدة</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">
                    تغيير كلمة المرور
                </button>
            </form>
        </div>
    </div>

    <script>
        // إدارة التبويبات
        document.querySelectorAll('.profile-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // إزالة التفعيل من جميع التبويبات
                document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // تفعيل التبويب المختار
                this.classList.add('active');
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });

        // التحقق من تطابق كلمات المرور
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('كلمات المرور غير متطابقة');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>