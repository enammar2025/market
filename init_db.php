<?php
// إنشاء قاعدة البيانات وإدراج البيانات التجريبية
$db_file = 'powermarket.db';

// حذف قاعدة البيانات القديمة إن وجدت
if (file_exists($db_file)) {
    unlink($db_file);
}

$pdo = new PDO("sqlite:$db_file");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// إنشاء الجداول
$sql = "
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    address TEXT,
    role VARCHAR(20) DEFAULT 'customer',
    status INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255),
    parent_id INTEGER,
    status INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2),
    cost DECIMAL(10,2),
    sku VARCHAR(50) UNIQUE NOT NULL,
    barcode VARCHAR(50),
    category_id INTEGER,
    image VARCHAR(255),
    status INTEGER DEFAULT 1,
    stock_quantity INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Product Images table
CREATE TABLE IF NOT EXISTS product_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    image VARCHAR(255) NOT NULL,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Carts table
CREATE TABLE IF NOT EXISTS carts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    session_id VARCHAR(255),
    coupon_code VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Cart Items table
CREATE TABLE IF NOT EXISTS cart_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cart_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    attributes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    order_number VARCHAR(50) UNIQUE,
    total DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status VARCHAR(20) DEFAULT 'pending',
    shipping_address TEXT,
    billing_address TEXT,
    notes TEXT,
    payment_gateway VARCHAR(50),
    ip_address VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Order Items table
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    product_id INTEGER,
    product_name VARCHAR(100) NOT NULL,
    product_price DECIMAL(10,2) NOT NULL,
    quantity INTEGER NOT NULL,
    total DECIMAL(10,2) NOT NULL
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
";

$pdo->exec($sql);

// إدراج البيانات التجريبية
$categories_sql = "
INSERT INTO categories (name, slug, description) VALUES 
('مولدات كهربائية', 'generators', 'مولدات كهربائية عالية الجودة لجميع الاستخدامات'),
('بطاريات', 'batteries', 'بطاريات متنوعة لجميع التطبيقات'),
('لوحات كهربائية', 'electrical-panels', 'لوحات توزيع كهربائية آمنة وموثوقة'),
('كابلات', 'cables', 'كابلات كهربائية عالية الجودة');
";

$products_sql = "
INSERT INTO products (name, slug, description, price, compare_price, cost, sku, category_id, stock_quantity, image) VALUES 
('مولد كهربائي 2000 كيلو فولت امبير', 'generator-2000kva', 'مولد كهربائي قوي بقدرة 2000 كيلو فولت امبير مناسب للاستخدامات الصناعية والتجارية الكبيرة. يتميز بالكفاءة العالية والموثوقية في التشغيل المستمر مع أنظمة حماية متقدمة', 600000.00, 650000.00, 550000.00, 'GEN-2000KVA', 1, 3, 'https://images.unsplash.com/photo-1621905252507-b35492cc74b4?w=400&h=300&fit=crop'),
('مولد كهربائي 1000 كيلو فولت امبير', 'generator-1000kva', 'مولد كهربائي متوسط القدرة بـ 1000 كيلو فولت امبير مثالي للمشاريع المتوسطة والمنشآت التجارية. يوفر أداء موثوق واستهلاك وقود اقتصادي', 500000.00, NULL, 450000.00, 'GEN-1000KVA', 1, 5, 'https://images.unsplash.com/photo-1621905252472-e8271d13c5b9?w=400&h=300&fit=crop'),
('مولد كهربائي 500 كيلو فولت امبير', 'generator-500kva', 'مولد كهربائي صغير الحجم بقدرة 500 كيلو فولت امبير مناسب للمنازل والمكاتب الصغيرة. تصميم مدمج وتشغيل هادئ', 300000.00, 320000.00, 280000.00, 'GEN-500KVA', 1, 8, 'https://images.unsplash.com/photo-1588964895597-cfccd6e2dbf9?w=400&h=300&fit=crop'),
('بطارية 120 أمبير', 'battery-120ah', 'بطارية عالية الأداء بقدرة 120 أمبير مناسبة للأنظمة الشمسية والطوارئ. تتميز بعمر افتراضي طويل وشحن سريع مع تقنية الجل المتقدمة', 520.00, 550.00, 450.00, 'BAT-120AH', 2, 20, 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=400&h=300&fit=crop'),
('بطارية 200 أمبير', 'battery-200ah', 'بطارية قوية بقدرة 200 أمبير للاستخدامات الثقيلة والأنظمة الكبيرة. مقاومة للتآكل ومناسبة للظروف الجوية القاسية', 850.00, 900.00, 750.00, 'BAT-200AH', 2, 15, 'https://images.unsplash.com/photo-1609091839479-c9c4a7a4e7e1?w=400&h=300&fit=crop'),
('بطارية 80 أمبير', 'battery-80ah', 'بطارية متوسطة الحجم بقدرة 80 أمبير مناسبة للأنظمة المنزلية الصغيرة', 350.00, 380.00, 300.00, 'BAT-80AH', 2, 25, 'https://images.unsplash.com/photo-1609091839311-d5365f9ff1c5?w=400&h=300&fit=crop'),
('لوحة توزيع كهربائية رئيسية', 'electrical-panel-main', 'لوحة توزيع كهربائية رئيسية مع حماية شاملة للدوائر وأنظمة الأمان المتقدمة. تتضمن قواطع حماية وعدادات رقمية', 500.00, NULL, 400.00, 'PANEL-MAIN', 3, 15, 'https://images.unsplash.com/photo-1621839673705-6617adf9e890?w=400&h=300&fit=crop'),
('لوحة فرعية 12 دائرة', 'sub-panel-12circuit', 'لوحة توزيع فرعية بـ 12 دائرة مناسبة للتوزيعات الصغيرة. تصميم مدمج وسهولة في التركيب والصيانة', 250.00, 280.00, 200.00, 'PANEL-SUB12', 3, 25, 'https://images.unsplash.com/photo-1621839673705-6617adf9e890?w=400&h=300&fit=crop'),
('قاطع كهربائي 100 أمبير', 'circuit-breaker-100a', 'قاطع كهربائي عالي الجودة بقدرة 100 أمبير للحماية من التيار الزائد. مصنوع من مواد عالية الجودة ومقاوم للحرارة', 150.00, 180.00, 120.00, 'CB-100A', 3, 50, 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=400&h=300&fit=crop'),
('قاطع كهربائي 63 أمبير', 'circuit-breaker-63a', 'قاطع كهربائي متوسط بقدرة 63 أمبير مناسب للدوائر المتوسطة', 90.00, 110.00, 75.00, 'CB-63A', 3, 80, 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=400&h=300&fit=crop'),
('كابل كهربائي 4×25 مم', 'cable-4x25mm', 'كابل كهربائي مقاوم للحريق 4×25 مم مناسب للتمديدات الكهربائية الرئيسية. معايير دولية ومقاوم للرطوبة', 35.00, NULL, 28.00, 'CABLE-4X25', 4, 100, 'https://images.unsplash.com/photo-1621905252669-bb5c4e6bb25e?w=400&h=300&fit=crop'),
('كابل كهربائي 3×16 مم', 'cable-3x16mm', 'كابل كهربائي بمقطع 3×16 مم للتمديدات الفرعية. مرونة عالية وسهولة في التركيب', 22.00, 25.00, 18.00, 'CABLE-3X16', 4, 150, 'https://images.unsplash.com/photo-1621905252669-bb5c4e6bb25e?w=400&h=300&fit=crop'),
('كابل كهربائي 2.5×2 مم', 'cable-2.5x2mm', 'كابل كهربائي رفيع 2.5×2 مم للإضاءة والمقابس العادية', 12.00, NULL, 9.00, 'CABLE-2.5X2', 4, 200, 'https://images.unsplash.com/photo-1621905252669-bb5c4e6bb25e?w=400&h=300&fit=crop');
";

$settings_sql = "
INSERT INTO settings (setting_key, setting_value) VALUES 
('site_name', 'متجر الطاقة الكهربائية'),
('currency', 'ر.س'),
('items_per_page', '12'),
('default_product_image', 'assets/images/placeholder.jpg');
";

$pdo->exec($categories_sql);
$pdo->exec($products_sql);
$pdo->exec($settings_sql);

echo "تم إنشاء قاعدة البيانات وإدراج البيانات بنجاح!\n";
echo "الملف: $db_file\n";
?>