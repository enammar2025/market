<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Product.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$product = new Product($db);

// جلب قائمة الأمنيات
$user_id = $_SESSION['user_id'];
$wishlist_query = "SELECT p.* FROM products p 
                   JOIN user_wishlist w ON p.id = w.product_id 
                   WHERE w.user_id = ? AND p.status = 1 
                   ORDER BY w.created_at DESC";
$stmt = $db->prepare($wishlist_query);
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// معالجة حذف من قائمة الأمنيات
if (isset($_POST['remove_from_wishlist'])) {
    $product_id = $_POST['product_id'];
    $delete_query = "DELETE FROM user_wishlist WHERE user_id = ? AND product_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    if ($delete_stmt->execute([$user_id, $product_id])) {
        header('Location: wishlist.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الأمنيات - متجر الطاقة الكهربائية</title>
    <meta name="description" content="قائمة المنتجات المفضلة لديك في متجر الطاقة الكهربائية">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .wishlist-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }
        .wishlist-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .item-content {
            padding: 20px;
        }
        .item-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .item-price {
            font-size: 20px;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .item-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .empty-icon {
            font-size: 64px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        .stock-status {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            display: inline-block;
        }
        .in-stock {
            background: #d4edda;
            color: #155724;
        }
        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="wishlist-container">
        <div class="wishlist-header">
            <h1><i class="fas fa-heart"></i> قائمة الأمنيات</h1>
            <p>المنتجات المفضلة لديك (<?php echo count($wishlist_items); ?> منتج)</p>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <div class="empty-icon">💔</div>
                <h2>قائمة الأمنيات فارغة</h2>
                <p>لم تقم بإضافة أي منتجات لقائمة الأمنيات بعد</p>
                <a href="index.php" class="btn btn-primary" style="margin-top: 20px; display: inline-block;">
                    تصفح المنتجات
                </a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                <div class="wishlist-item">
                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" 
                         class="item-image" onerror="this.src='assets/images/placeholder.jpg'">
                    
                    <div class="item-content">
                        <div class="stock-status <?php echo $item['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php echo $item['stock_quantity'] > 0 ? 'متوفر' : 'غير متوفر'; ?>
                        </div>
                        
                        <h3 class="item-title"><?php echo $item['name']; ?></h3>
                        
                        <div class="item-price">
                            <?php if ($item['compare_price']): ?>
                                <span style="text-decoration: line-through; color: #7f8c8d; font-size: 14px;">
                                    <?php echo number_format($item['compare_price'], 2); ?> ر.س
                                </span><br>
                            <?php endif; ?>
                            <?php echo number_format($item['price'], 2); ?> ر.س
                        </div>
                        
                        <div class="item-actions">
                            <?php if ($item['stock_quantity'] > 0): ?>
                                <button class="btn btn-primary" onclick="addToCart(<?php echo $item['id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i> أضف للسلة
                                </button>
                            <?php else: ?>
                                <button class="btn btn-primary" disabled style="opacity: 0.5;">
                                    غير متوفر
                                </button>
                            <?php endif; ?>
                            
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_from_wishlist" class="btn btn-danger"
                                        onclick="return confirm('إزالة من قائمة الأمنيات؟')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                        
                        <a href="product.php?id=<?php echo $item['id']; ?>" 
                           style="display: block; text-align: center; margin-top: 10px; color: #3498db;">
                            عرض التفاصيل
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function addToCart(productId) {
            fetch('api/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('تم إضافة المنتج للسلة', 'success');
                    updateCartCount();
                } else {
                    showNotification(data.message || 'حدث خطأ', 'error');
                }
            })
            .catch(error => {
                showNotification('حدث خطأ في الاتصال', 'error');
            });
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                left: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                z-index: 10000;
                font-weight: bold;
            `;
            
            if (type === 'success') {
                notification.style.background = '#27ae60';
            } else {
                notification.style.background = '#e74c3c';
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        function updateCartCount() {
            // تحديث عداد السلة
            fetch('api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount && data.count !== undefined) {
                        cartCount.textContent = data.count;
                    }
                });
        }
    </script>
</body>
</html>