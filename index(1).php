<?php
// إعدادات عرض الأخطاء
ini_set('display_errors', 1);
error_reporting(E_ALL);

// الاتصال بقاعدة البيانات
require_once 'config/database.php';
require_once 'classes/Product.php';
require_once 'classes/Category.php';

// إنشاء اتصال بقاعدة البيانات
$database = new Database();
$conn = $database->getConnection();

// إنشاء كائنات الفئات والمنتجات
$product = new Product($conn);
$category = new Category($conn);

// معالجة الفلاتر والبحث
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

$limit = 12;
$products_result = $product->read($page, $limit, $category_id, $search);
$total_products = $product->count($category_id, $search);
$total_pages = ceil($total_products / $limit);

// جلب التصنيفات
$categories_result = $category->read();
$categories = [];
while ($row = $categories_result->fetch(PDO::FETCH_ASSOC)) {
    $categories[] = $row;
}

// جلب المنتجات المميزة
$featured_result = $product->getFeatured(6);
$featured_products = [];
while ($row = $featured_result->fetch(PDO::FETCH_ASSOC)) {
    $featured_products[] = $row;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>PowerHub - المتجر الإلكتروني</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa; 
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .cart-icon {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: #007bff;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .cart-icon:hover {
            transform: scale(1.1);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: red;
            color: white;
            padding: 2px 6px;
            border-radius: 50%;
            font-size: 12px;
            display: none;
        }

        .sidebar {
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .product-card img {
                height: 150px;
            }
        }
    </style>
</head>
<body>

<!-- عنوان الموقع -->
<h1 class="text-center my-4">منتجات PowerHub</h1>

<div class="container-fluid">
    <div class="row">
        <!-- الشريط الجانبي -->
        <div class="col-md-3">
            <div class="card sidebar shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">التصنيفات</h5>
                </div>
                <ul class="list-group list-group-flush" id="category-list">
                    <li class="list-group-item active" data-cat-id="all">الكل</li>
                    <?php foreach ($categories as $cat): ?>
                        <li class="list-group-item" data-cat-id="<?= htmlspecialchars($cat['id']) ?>">
                            <?= htmlspecialchars($cat['name']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن منتج...">
            </div>

            <button id="resetFilters" class="btn btn-outline-danger w-100">إعادة التعيين</button>
        </div>

        <!-- قائمة المنتجات -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>عدد المنتجات المعروضة: <span id="product-count"><?= $total_products ?></span></div>
            </div>
            <div class="row g-4" id="products-container">
                <?php while ($row = $products_result->fetch(PDO::FETCH_ASSOC)): ?>

                    <div class="col-md-4 col-sm-6 product-item"
                         data-cat-id="<?= htmlspecialchars($row['category_id']) ?>"
                         data-name="<?= mb_strtolower(htmlspecialchars($row['name'])) ?>"
                         data-desc="<?= mb_strtolower(htmlspecialchars($row['description'])) ?>">
                        <div class="card product-card h-100 shadow-sm">
                            <?php
$imageFile = 'default.jpg';

if (!empty($row['image_path'])) {
    $filename = basename($row['image_path']);
    $imagePath = __DIR__ . '/assets/images/products/' . $filename;

    if (file_exists($imagePath)) {
        $imageFile = $filename;
    }
}
?>

<?php
$imageFile = 'default.jpg';

if (!empty($row['image_path'])) {
    $filename = basename($row['image_path']);
    $filepath = __DIR__ . "/assets/images/products/{$filename}";

    if (file_exists($filepath)) {
        $imageFile = $filename;
    }
}
?>

<img src="/assets/images/products/<?= htmlspecialchars($imageFile) ?>"
     class="card-img-top"
     onerror="this.onerror=null; this.src='/assets/images/products/default.jpg';"
     alt="<?= htmlspecialchars($row['name'] ?? 'منتج') ?>">


                                 class="card-img-top"
                                 onerror="this.onerror=null; this.src='/assets/images/default.jpg';"
                                 alt="<?= htmlspecialchars($row['name']) ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($row['name']) ?></h5>
                                <p class="text-success fw-bold"><?= number_format($row['price'], 2) ?> ريال</p>
                                <p class="<?= $row['stock_quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $row['stock_quantity'] > 0 ? "متوفر" : "غير متوفر" ?>
                                </p>
                                <button class="btn btn-outline-primary add-to-cart-btn mt-auto"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-price="<?= $row['price'] ?>"
                                        data-image="/assets/images/products/<?= htmlspecialchars(basename($row['image_path'])) ?>"
                                        data-stock="<?= $row['stock_quantity'] ?>"
                                    <?= $row['stock_quantity'] <= 0 ? 'disabled' : '' ?>>
                                    أضف إلى السلة
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- التنقل بين الصفحات -->
            <nav aria-label="Pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(['category' => $category_id, 'search' => $search]) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- أيقونة السلة -->
<div class="cart-icon" id="cartIcon" title="سلة المشتريات">
    🛒
    <span class="cart-count" id="cartCount" style="display:none;">0</span>
</div>

<!-- شريط السلة الجانبي -->
<div id="cartSidebar" class="position-fixed end-0 top-0 h-100 bg-white shadow-lg p-3"
     style="width: 350px; transform: translateX(100%); transition: transform 0.3s;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>سلة المشتريات</h5>
        <button id="closeCart" class="btn btn-close"></button>
    </div>
    <div id="cartItems">
        <p class="text-center text-muted">السلة فارغة</p>
    </div>
    <div class="mt-auto">
        <h6 class="text-end">المجموع: <span id="cartTotal">0.00</span> ريال</h6>
        <button id="checkoutBtn" class="btn btn-success w-100 mt-2" disabled>إتمام الشراء</button>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> 

<!-- JavaScript للتفاعل -->
<script>
    const productsContainer = document.getElementById("products-container");
    const categoryList = document.getElementById("category-list");
    const searchInput = document.getElementById("searchInput");
    const resetFilters = document.getElementById("resetFilters");
    const productCount = document.getElementById("product-count");
    const cartItemsContainer = document.getElementById("cartItems");
    const cartTotalSpan = document.getElementById("cartTotal");
    const cartCountSpan = document.getElementById("cartCount");
    const cartIcon = document.getElementById("cartIcon");
    const cartSidebar = document.getElementById("cartSidebar");
    const closeCart = document.getElementById("closeCart");
    const checkoutBtn = document.getElementById("checkoutBtn");

    let cart = JSON.parse(localStorage.getItem('cart')) || {};

    function renderProducts(filteredProducts = Array.from(document.querySelectorAll('.product-item'))) {
        filteredProducts.forEach(product => product.style.display = '');
        productCount.textContent = document.querySelectorAll('.product-item:not([style*="display: none"])').length;
    }

    function filterProducts() {
        const selectedCat = document.querySelector('#category-list li.active').dataset.catId;
        const term = searchInput.value.toLowerCase();
        const products = document.querySelectorAll('.product-item');

        let visibleCount = 0;

        products.forEach(product => {
            const catId = product.dataset.catId;
            const name = product.dataset.name;
            const desc = product.dataset.desc;

            const categoryMatch = selectedCat === 'all' || catId === selectedCat;
            const searchMatch = name.includes(term) || desc.includes(term);

            if (categoryMatch && searchMatch) {
                product.style.display = '';
                visibleCount++;
            } else {
                product.style.display = 'none';
            }
        });

        productCount.textContent = visibleCount;
    }

    function updateCartUI() {
        cartItemsContainer.innerHTML = "";
        let total = 0;
        let count = 0;

        Object.entries(cart).forEach(([id, item]) => {
            total += item.price * item.quantity;
            count += item.quantity;
            const div = document.createElement("div");
            div.className = "d-flex justify-content-between align-items-center mb-2";
            div.innerHTML = `
                ${item.name} × ${item.quantity}
                <button class="btn btn-sm btn-danger remove-from-cart" data-id="${id}">إزالة</button>
            `;
            cartItemsContainer.appendChild(div);
        });

        cartTotalSpan.textContent = total.toFixed(2);
        cartCountSpan.textContent = count;
        cartCountSpan.style.display = count > 0 ? "inline-block" : "none";
        checkoutBtn.disabled = count === 0;
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    productsContainer.addEventListener("click", e => {
        if (e.target.classList.contains("add-to-cart-btn")) {
            const id = e.target.dataset.id;
            const product = {
                id,
                name: e.target.dataset.name,
                price: parseFloat(e.target.dataset.price),
                image: e.target.dataset.image,
                stock: parseInt(e.target.dataset.stock)
            };

            if (!cart[id]) {
                cart[id] = {...product, quantity: 1};
            } else if (cart[id].quantity < product.stock) {
                cart[id].quantity++;
            } else {
                alert("الكمية غير متوفرة.");
                return;
            }

            updateCartUI();
        }
    });

    cartItemsContainer.addEventListener("click", e => {
        if (e.target.classList.contains("remove-from-cart")) {
            const id = e.target.dataset.id;
            delete cart[id];
            updateCartUI();
        }
    });

    cartIcon.addEventListener("click", () => {
        cartSidebar.style.transform = "translateX(0)";
    });

    closeCart.addEventListener("click", () => {
        cartSidebar.style.transform = "translateX(100%)";
    });

    checkoutBtn.addEventListener("click", async () => {
        if (Object.keys(cart).length === 0) return;

        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({checkout: true, cartItems: cart})
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            cart = {};
            updateCartUI();
            location.reload();
        } else {
            alert("حدث خطأ أثناء الشراء: " + result.message);
        }
    });

    searchInput.addEventListener("input", filterProducts);

    resetFilters.addEventListener("click", () => {
        searchInput.value = "";
        document.querySelectorAll("#category-list li").forEach(li => li.classList.remove("active"));
        document.querySelector("#category-list li").classList.add("active");
        filterProducts();
    });

    categoryList.addEventListener("click", e => {
        if (e.target.tagName === "LI") {
            document.querySelectorAll("#category-list li").forEach(li => li.classList.remove("active"));
            e.target.classList.add("active");
            filterProducts();
        }
    });

    window.addEventListener("load", () => {
        renderProducts();
        updateCartUI();
    });
</script>

</body>
</html>