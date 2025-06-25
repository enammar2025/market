<?php
require_once 'config.php';

header('Content-Type: application/json');

// التحقق من أن الطلب POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة الطلب غير مسموحة']);
    exit;
}

// قراءة بيانات JSON المرسلة
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// التحقق من البيانات
if (!isset($data['product_id']) || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة']);
    exit;
}

$productId = (int)$data['product_id'];
$quantity = (int)$data['quantity'];

// هنا يمكنك إضافة كود إضافة المنتج إلى السلة في قاعدة البيانات
// هذا مثال مبسط:

// 1. التحقق من وجود المنتج والكمية المتاحة
$stmt = $conn->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND status = 1");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'المنتج غير موجود']);
    exit;
}

$product = $result->fetch_assoc();

if ($product['stock_quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'الكمية المطلوبة غير متوفرة في المخزون']);
    exit;
}

// 2. إضافة المنتج إلى سلة المستخدم (هذا مثال - يجب تكييفه مع نظامك)
// في حالة وجود مستخدم مسجل الدخول
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // التحقق مما إذا كان المنتج موجوداً بالفعل في السلة
    $stmt = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE user_id = ?) AND product_id = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // تحديث الكمية إذا كان المنتج موجوداً
        $item = $result->fetch_assoc();
        $newQuantity = $item['quantity'] + $quantity;
        
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $newQuantity, $item['id']);
        $stmt->execute();
    } else {
        // إضافة منتج جديد إلى السلة
        // أولاً الحصول على سلة المستخدم أو إنشاء واحدة جديدة
        $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // إنشاء سلة جديدة
            $stmt = $conn->prepare("INSERT INTO carts (user_id) VALUES (?)");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $cartId = $conn->insert_id;
        } else {
            $cart = $result->fetch_assoc();
            $cartId = $cart['id'];
        }
        
        // الحصول على سعر المنتج
        $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        // إضافة العنصر إلى السلة
        $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $cartId, $productId, $quantity, $product['price']);
        $stmt->execute();
    }
} else {
    // معالجة سلة الضيوف (Guest Cart) باستخدام session
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }
}

echo json_encode(['success' => true, 'message' => 'تمت إضافة المنتج إلى السلة']);
?>