<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Cart.php';
require_once 'classes/Order.php';
require_once 'classes/Payment.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();

$cart = new Cart($db);
$order = new Order($db);
$payment = new Payment($db);
$user = new User($db);

// التحقق من وجود عناصر في السلة
$session_id = session_id();
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $cart_items = $cart->getByUserId($user_id);
} else {
    $cart_items = $cart->getBySessionId($session_id);
}

if (empty($cart_items)) {
    header('Location: cart.php');
    exit();
}

// حساب الإجمالي
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$tax_rate = 0.15; // ضريبة القيمة المضافة 15%
$tax_amount = $subtotal * $tax_rate;
$shipping_fee = 25.00; // رسوم الشحن
$total = $subtotal + $tax_amount + $shipping_fee;

$user_data = null;
if ($user_id) {
    $user_data = $user->getUserById($user_id);
}

$message = '';
$message_type = '';

// معالجة الطلب
if ($_POST && isset($_POST['place_order'])) {
    $shipping_address = $_POST['shipping_address'];
    $billing_address = $_POST['billing_address'] ?: $shipping_address;
    $payment_method = $_POST['payment_method'];
    $notes = $_POST['notes'] ?? '';
    
    // إنشاء الطلب
    $order_id = $order->createOrder($user_id, $total, $payment_method, $shipping_address, $billing_address, $notes, $cart_items);
    
    if ($order_id) {
        // معالجة الدفع حسب الطريقة المختارة
        $payment_result = ['success' => false];
        
        switch ($payment_method) {
            case 'local_wallet':
                $phone = $_POST['wallet_phone'];
                $wallet_type = $_POST['wallet_type'];
                $payment_result = $payment->processLocalWallet($order_id, $phone, $total, $wallet_type);
                break;
                
            case 'credit_card':
                $card_number = $_POST['card_number'];
                $expiry_month = $_POST['expiry_month'];
                $expiry_year = $_POST['expiry_year'];
                $cvv = $_POST['cvv'];
                $payment_result = $payment->processCreditCard($order_id, $card_number, $expiry_month, $expiry_year, $cvv, $total);
                break;
                
            case 'bank_transfer':
                $bank_name = $_POST['bank_name'];
                $account_number = $_POST['account_number'];
                $payment_result = $payment->processBankTransfer($order_id, $bank_name, $account_number, $total);
                break;
                
            case 'cash_on_delivery':
                $payment_result = $payment->processCOD($order_id, $shipping_address);
                break;
        }
        
        if ($payment_result['success']) {
            // مسح السلة
            if ($user_id) {
                $cart->clearByUserId($user_id);
            } else {
                $cart->clearBySessionId($session_id);
            }
            
            // إعادة توجيه لصفحة تأكيد الطلب
            header('Location: order-confirmation.php?order_id=' . $order_id);
            exit();
        } else {
            $message = $payment_result['message'];
            $message_type = 'error';
        }
    } else {
        $message = 'حدث خطأ في إنشاء الطلب';
        $message_type = 'error';
    }
}

$payment_methods = $payment->getPaymentMethods();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الطلب - متجر الطاقة الكهربائية</title>
    <meta name="description" content="أتمم طلبك من متجر الطاقة الكهربائية بأمان وسهولة مع خيارات دفع متعددة">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-top: 20px;
        }
        .checkout-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
        .payment-methods {
            display: grid;
            gap: 15px;
        }
        .payment-method {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #3498db;
        }
        .payment-method.selected {
            border-color: #3498db;
            background: #f8f9fa;
        }
        .payment-method input[type="radio"] {
            margin-left: 10px;
        }
        .payment-details {
            display: none;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .payment-details.active {
            display: block;
        }
        .order-summary {
            position: sticky;
            top: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        .summary-total {
            border-top: 2px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
        }
        .place-order-btn {
            width: 100%;
            padding: 18px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 20px;
        }
        .place-order-btn:hover {
            background: #2ecc71;
        }
        .cart-items {
            max-height: 300px;
            overflow-y: auto;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .cart-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
            margin-left: 10px;
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .item-price {
            color: #7f8c8d;
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
        .security-badge {
            display: flex;
            align-items: center;
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
            color: #155724;
            font-size: 14px;
        }
        .security-badge i {
            margin-left: 8px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="checkout-container">
        <h1>إتمام الطلب</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="checkout-form">
            <div class="checkout-grid">
                <div class="checkout-main">
                    <!-- معلومات الشحن -->
                    <div class="checkout-section">
                        <h2>معلومات الشحن</h2>
                        <div class="form-group">
                            <label for="shipping_address">عنوان الشحن</label>
                            <textarea id="shipping_address" name="shipping_address" required 
                                      placeholder="أدخل العنوان الكامل مع المدينة والمنطقة"><?php echo $user_data['address'] ?? ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="billing_address">عنوان الفواتير (اختياري)</label>
                            <textarea id="billing_address" name="billing_address" 
                                      placeholder="اتركه فارغاً لاستخدام نفس عنوان الشحن"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="notes">ملاحظات إضافية</label>
                            <textarea id="notes" name="notes" 
                                      placeholder="أي ملاحظات خاصة بالطلب"></textarea>
                        </div>
                    </div>

                    <!-- طرق الدفع -->
                    <div class="checkout-section">
                        <h2>طريقة الدفع</h2>
                        <div class="payment-methods">
                            <!-- المحفظة الرقمية -->
                            <div class="payment-method" data-method="local_wallet">
                                <label>
                                    <input type="radio" name="payment_method" value="local_wallet">
                                    <i class="fas fa-mobile-alt"></i>
                                    المحفظة الرقمية (مدى، STC Pay)
                                </label>
                                <div class="payment-details">
                                    <div class="form-group">
                                        <label for="wallet_type">نوع المحفظة</label>
                                        <select id="wallet_type" name="wallet_type">
                                            <option value="mada">مدى</option>
                                            <option value="stc_pay">STC Pay</option>
                                            <option value="apple_pay">Apple Pay</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="wallet_phone">رقم الهاتف</label>
                                        <input type="tel" id="wallet_phone" name="wallet_phone" placeholder="05xxxxxxxx">
                                    </div>
                                </div>
                            </div>

                            <!-- البطاقة الائتمانية -->
                            <div class="payment-method" data-method="credit_card">
                                <label>
                                    <input type="radio" name="payment_method" value="credit_card">
                                    <i class="fas fa-credit-card"></i>
                                    البطاقة الائتمانية (فيزا، ماستركارد)
                                </label>
                                <div class="payment-details">
                                    <div class="form-group">
                                        <label for="card_number">رقم البطاقة</label>
                                        <input type="text" id="card_number" name="card_number" 
                                               placeholder="1234 5678 9012 3456" maxlength="19">
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                        <div class="form-group">
                                            <label for="expiry_month">الشهر</label>
                                            <select id="expiry_month" name="expiry_month">
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="expiry_year">السنة</label>
                                            <select id="expiry_year" name="expiry_year">
                                                <?php for ($i = date('Y'); $i <= date('Y') + 10; $i++): ?>
                                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="cvv">CVV</label>
                                            <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- التحويل البنكي -->
                            <div class="payment-method" data-method="bank_transfer">
                                <label>
                                    <input type="radio" name="payment_method" value="bank_transfer">
                                    <i class="fas fa-university"></i>
                                    التحويل البنكي
                                </label>
                                <div class="payment-details">
                                    <?php $bank_details = $payment->getBankDetails(); ?>
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                                        <h4>تفاصيل الحساب البنكي:</h4>
                                        <p><strong>اسم البنك:</strong> <?php echo $bank_details['bank_name']; ?></p>
                                        <p><strong>رقم الحساب:</strong> <?php echo $bank_details['account_number']; ?></p>
                                        <p><strong>اسم الحساب:</strong> <?php echo $bank_details['account_name']; ?></p>
                                    </div>
                                    <div class="form-group">
                                        <label for="bank_name">اسم البنك المحول منه</label>
                                        <input type="text" id="bank_name" name="bank_name">
                                    </div>
                                    <div class="form-group">
                                        <label for="account_number">رقم الحساب المحول منه</label>
                                        <input type="text" id="account_number" name="account_number">
                                    </div>
                                </div>
                            </div>

                            <!-- الدفع عند التسليم -->
                            <div class="payment-method" data-method="cash_on_delivery">
                                <label>
                                    <input type="radio" name="payment_method" value="cash_on_delivery">
                                    <i class="fas fa-money-bill-wave"></i>
                                    الدفع عند التسليم (+<?php echo number_format($payment_methods['cash_on_delivery']['extra_fee'], 2); ?> ر.س)
                                </label>
                                <div class="payment-details">
                                    <p>سيتم تحصيل المبلغ عند استلام الطلب مع رسوم إضافية <?php echo number_format($payment_methods['cash_on_delivery']['extra_fee'], 2); ?> ر.س</p>
                                </div>
                            </div>
                        </div>

                        <div class="security-badge">
                            <i class="fas fa-shield-alt"></i>
                            جميع معلومات الدفع محمية ومشفرة بأعلى معايير الأمان
                        </div>
                    </div>
                </div>

                <!-- ملخص الطلب -->
                <div class="order-summary">
                    <div class="checkout-section">
                        <h2>ملخص الطلب</h2>
                        
                        <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                <div class="item-details">
                                    <div class="item-name"><?php echo $item['name']; ?></div>
                                    <div class="item-price">
                                        <?php echo $item['quantity']; ?> × <?php echo number_format($item['price'], 2); ?> ر.س
                                    </div>
                                </div>
                                <div class="item-total">
                                    <?php echo number_format($item['price'] * $item['quantity'], 2); ?> ر.س
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="summary-item">
                            <span>المجموع الفرعي:</span>
                            <span><?php echo number_format($subtotal, 2); ?> ر.س</span>
                        </div>
                        <div class="summary-item">
                            <span>ضريبة القيمة المضافة (15%):</span>
                            <span><?php echo number_format($tax_amount, 2); ?> ر.س</span>
                        </div>
                        <div class="summary-item">
                            <span>رسوم الشحن:</span>
                            <span><?php echo number_format($shipping_fee, 2); ?> ر.س</span>
                        </div>
                        <div class="summary-item summary-total">
                            <span>المجموع الكلي:</span>
                            <span id="final-total"><?php echo number_format($total, 2); ?> ر.س</span>
                        </div>

                        <button type="submit" name="place_order" class="place-order-btn">
                            <i class="fas fa-lock"></i>
                            تأكيد الطلب والدفع
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // إدارة طرق الدفع
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // إزالة التحديد من جميع الطرق
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                    m.querySelector('.payment-details').classList.remove('active');
                });
                
                // تحديد الطريقة المختارة
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
                this.querySelector('.payment-details').classList.add('active');
                
                // تحديث المجموع في حالة الدفع عند التسليم
                updateTotal();
            });
        });

        // تنسيق رقم البطاقة الائتمانية
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // تحديث المجموع الكلي
        function updateTotal() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            let total = <?php echo $subtotal + $tax_amount + $shipping_fee; ?>;
            
            if (selectedMethod && selectedMethod.value === 'cash_on_delivery') {
                total += <?php echo $payment_methods['cash_on_delivery']['extra_fee']; ?>;
            }
            
            document.getElementById('final-total').textContent = total.toFixed(2) + ' ر.س';
        }

        // التحقق من صحة النموذج
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            
            if (!selectedMethod) {
                e.preventDefault();
                alert('يرجى اختيار طريقة الدفع');
                return;
            }
            
            // التحقق من البيانات المطلوبة لكل طريقة دفع
            if (selectedMethod.value === 'local_wallet') {
                const phone = document.getElementById('wallet_phone').value;
                if (!phone) {
                    e.preventDefault();
                    alert('يرجى إدخال رقم الهاتف');
                    return;
                }
            }
            
            if (selectedMethod.value === 'credit_card') {
                const cardNumber = document.getElementById('card_number').value;
                const cvv = document.getElementById('cvv').value;
                
                if (!cardNumber || !cvv) {
                    e.preventDefault();
                    alert('يرجى إدخال بيانات البطاقة كاملة');
                    return;
                }
            }
        });
    </script>
</body>
</html>