<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Order.php';

if (!isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$order = new Order($db);

$order_data = $order->getOrderById($_GET['order_id']);
$order_items = $order->getOrderItems($_GET['order_id']);

if (!$order_data) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الطلب - متجر الطاقة الكهربائية</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .success-header {
            background: #d4edda;
            color: #155724;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        .success-header i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .order-details {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .detail-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .items-table th,
        .items-table td {
            text-align: right;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .next-steps {
            background: #e8f4fd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="confirmation-container">
        <div class="success-header">
            <i class="fas fa-check-circle"></i>
            <h1>تم تأكيد طلبك بنجاح!</h1>
            <p>رقم الطلب: <strong><?php echo $order_data['order_number']; ?></strong></p>
        </div>

        <div class="order-details">
            <h2>تفاصيل الطلب</h2>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">تاريخ الطلب</div>
                    <div><?php echo date('Y-m-d H:i', strtotime($order_data['created_at'])); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">المبلغ الإجمالي</div>
                    <div><?php echo number_format($order_data['total'], 2); ?> ر.س</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">طريقة الدفع</div>
                    <div>
                        <?php
                        $payment_methods = [
                            'local_wallet' => 'المحفظة الرقمية',
                            'credit_card' => 'البطاقة الائتمانية',
                            'bank_transfer' => 'التحويل البنكي',
                            'cash_on_delivery' => 'الدفع عند التسليم'
                        ];
                        echo $payment_methods[$order_data['payment_method']] ?? $order_data['payment_method'];
                        ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">حالة الطلب</div>
                    <div>
                        <span class="status-badge status-<?php echo $order_data['status']; ?>">
                            <?php
                            $statuses = [
                                'pending' => 'في الانتظار',
                                'processing' => 'قيد المعالجة',
                                'shipped' => 'تم الشحن',
                                'delivered' => 'تم التسليم'
                            ];
                            echo $statuses[$order_data['status']] ?? $order_data['status'];
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-label">عنوان الشحن</div>
                <div><?php echo nl2br(htmlspecialchars($order_data['shipping_address'])); ?></div>
            </div>

            <h3>المنتجات المطلوبة</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>المنتج</th>
                        <th>السعر</th>
                        <th>الكمية</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo $item['product_name']; ?></td>
                        <td><?php echo number_format($item['product_price'], 2); ?> ر.س</td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['total'], 2); ?> ر.س</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="next-steps">
            <h3>الخطوات التالية</h3>
            <ul>
                <li>سيتم مراجعة طلبك وتأكيده خلال 24 ساعة</li>
                <li>ستصلك رسالة نصية أو بريد إلكتروني عند تغيير حالة الطلب</li>
                <li>يمكنك تتبع طلبك من خلال الملف الشخصي</li>
                <?php if ($order_data['payment_method'] == 'bank_transfer'): ?>
                <li>يرجى إرسال إيصال التحويل البنكي عبر واتساب أو البريد الإلكتروني</li>
                <?php endif; ?>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="btn btn-primary">العودة للمتجر</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-secondary">عرض طلباتي</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>