<?php
// إعداد PHP built-in server مع قاعدة بيانات SQLite للاختبار
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// إنشاء قاعدة بيانات SQLite للاختبار إذا لم تكن موجودة
$db_file = 'powermarket.db';

if (!file_exists($db_file)) {
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

    -- Insert sample categories
    INSERT INTO categories (name, slug, description) VALUES 
    ('مولدات كهربائية', 'generators', 'مولدات كهربائية عالية الجودة لجميع الاستخدامات'),
    ('بطاريات', 'batteries', 'بطاريات متنوعة لجميع التطبيقات'),
    ('لوحات كهربائية', 'electrical-panels', 'لوحات توزيع كهربائية آمنة وموثوقة'),
    ('كابلات', 'cables', 'كابلات كهربائية عالية الجودة');

    -- Insert sample products
    INSERT INTO products (name, slug, description, price, compare_price, cost, sku, category_id, stock_quantity) VALUES 
    ('مولد كهربائي 2000 كيلو فولت امبير', 'generator-2000kva', 'مولد كهربائي قوي بقدرة 2000 كيلو فولت امبير مناسب للاستخدامات الصناعية والتجارية الكبيرة. يتميز بالكفاءة العالية والموثوقية في التشغيل المستمر', 600000.00, 650000.00, 550000.00, 'GEN-2000KVA', 1, 3),
    ('مولد كهربائي 1000 كيلو فولت امبير', 'generator-1000kva', 'مولد كهربائي متوسط القدرة بـ 1000 كيلو فولت امبير مثالي للمشاريع المتوسطة والمنشآت التجارية', 500000.00, NULL, 450000.00, 'GEN-1000KVA', 1, 5),
    ('مولد كهربائي 500 كيلو فولت امبير', 'generator-500kva', 'مولد كهربائي صغير الحجم بقدرة 500 كيلو فولت امبير مناسب للمنازل والمكاتب الصغيرة', 300000.00, 320000.00, 280000.00, 'GEN-500KVA', 1, 8),
    ('بطارية 120 أمبير', 'battery-120ah', 'بطارية عالية الأداء بقدرة 120 أمبير مناسبة للأنظمة الشمسية والطوارئ. تتميز بعمر افتراضي طويل وشحن سريع', 520.00, 550.00, 450.00, 'BAT-120AH', 2, 20),
    ('بطارية 200 أمبير', 'battery-200ah', 'بطارية قوية بقدرة 200 أمبير للاستخدامات الثقيلة والأنظمة الكبيرة', 850.00, 900.00, 750.00, 'BAT-200AH', 2, 15),
    ('لوحة توزيع كهربائية رئيسية', 'electrical-panel-main', 'لوحة توزيع كهربائية رئيسية مع حماية شاملة للدوائر وأنظمة الأمان المتقدمة', 500.00, NULL, 400.00, 'PANEL-MAIN', 3, 15),
    ('لوحة فرعية 12 دائرة', 'sub-panel-12circuit', 'لوحة توزيع فرعية بـ 12 دائرة مناسبة للتوزيعات الصغيرة', 250.00, 280.00, 200.00, 'PANEL-SUB12', 3, 25),
    ('قاطع كهربائي 100 أمبير', 'circuit-breaker-100a', 'قاطع كهربائي عالي الجودة بقدرة 100 أمبير للحماية من التيار الزائد', 150.00, 180.00, 120.00, 'CB-100A', 3, 50),
    ('قاطع كهربائي 63 أمبير', 'circuit-breaker-63a', 'قاطع كهربائي متوسط بقدرة 63 أمبير مناسب للدوائر المتوسطة', 90.00, 110.00, 75.00, 'CB-63A', 3, 80),
    ('كابل كهربائي 4×25 مم', 'cable-4x25mm', 'كابل كهربائي مقاوم للحريق 4×25 مم مناسب للتمديدات الكهربائية الرئيسية', 35.00, NULL, 28.00, 'CABLE-4X25', 4, 100),
    ('كابل كهربائي 3×16 مم', 'cable-3x16mm', 'كابل كهربائي بمقطع 3×16 مم للتمديدات الفرعية', 22.00, 25.00, 18.00, 'CABLE-3X16', 4, 150),
    ('كابل كهربائي 2.5×2 مم', 'cable-2.5x2mm', 'كابل كهربائي رفيع 2.5×2 مم للإضاءة والمقابس العادية', 12.00, NULL, 9.00, 'CABLE-2.5X2', 4, 200);
    ";
    
    $pdo->exec($sql);
    echo "تم إنشاء قاعدة البيانات بنجاح\n";
}

// تشغيل السيرفر
echo "بدء تشغيل متجر الطاقة الكهربائية...\n";
echo "الموقع متاح على: http://localhost:8000\n";
echo "للإيقاف اضغط Ctrl+C\n\n";

// تشغيل PHP built-in server
exec('php -S 0.0.0.0:8000 -t . 2>&1', $output, $return_var);

if ($return_var !== 0) {
    echo "خطأ في تشغيل السيرفر: " . implode("\n", $output) . "\n";
} else {
    echo "تم إيقاف السيرفر\n";
}
?>