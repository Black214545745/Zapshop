<?php
echo "<h2>ตั้งค่าฐานข้อมูล PostgreSQL ใหม่</h2>";
echo "<p>กำลังสร้างโครงสร้างฐานข้อมูลตามที่กำหนด...</p>";

// ใช้ข้อมูลการเชื่อมต่อจาก config.php
$host = 'dpg-d2q1vder433s73dqf0lg-a.oregon-postgres.render.com';
$port = '5432';
$dbname = 'zapstock_db';
$user = 'zapstock_user';
$password = 'jb3uWpZlFoG3f2d1PI21ZFX0frHSGrDW';

// สร้าง Connection String
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

echo "<p style='color: green;'>✓ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";

// อ่านไฟล์ SQL และรัน
$sql_file = file_get_contents('database_schema.sql');

if (!$sql_file) {
    echo "<p style='color: red;'>✗ ไม่พบไฟล์ database_schema.sql</p>";
    echo "<p>กำลังสร้างโครงสร้างฐานข้อมูลแบบพื้นฐาน...</p>";
    
    // สร้างโครงสร้างพื้นฐาน
    $basic_sql = "
    -- สร้างตาราง users
    CREATE TABLE IF NOT EXISTS users (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        username VARCHAR(255) UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'user',
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );

    -- สร้างตาราง user_profiles
    CREATE TABLE IF NOT EXISTS user_profiles (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_user_profile
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE CASCADE
    );

    -- สร้างตาราง categories
    CREATE TABLE IF NOT EXISTS categories (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        name VARCHAR(255) UNIQUE NOT NULL,
        description TEXT,
        parent_id UUID,
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_category_parent
            FOREIGN KEY (parent_id)
            REFERENCES categories(id)
            ON DELETE SET NULL
    );

    -- สร้างตาราง suppliers
    CREATE TABLE IF NOT EXISTS suppliers (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        name VARCHAR(255) UNIQUE NOT NULL,
        contact_person VARCHAR(255),
        email VARCHAR(255),
        phone VARCHAR(20),
        address TEXT,
        tax_id VARCHAR(50),
        payment_terms INTEGER DEFAULT 30,
        is_active BOOLEAN DEFAULT true,
        notes TEXT,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );

    -- สร้างตาราง products
    CREATE TABLE IF NOT EXISTS products (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        name VARCHAR(255) NOT NULL,
        description TEXT,
        sku VARCHAR(100) UNIQUE,
        barcode VARCHAR(100),
        current_stock INTEGER NOT NULL DEFAULT 0 CHECK (current_stock >= 0),
        min_stock_quantity INTEGER NOT NULL DEFAULT 0 CHECK (min_stock_quantity >= 0),
        max_stock_quantity INTEGER,
        price DECIMAL(12,2),
        cost_price DECIMAL(12,2),
        weight DECIMAL(10,3),
        dimensions VARCHAR(100),
        image_url TEXT,
        status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'discontinued')),
        category_id UUID,
        supplier_id UUID,
        created_by UUID,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_product_category
            FOREIGN KEY (category_id)
            REFERENCES categories(id)
            ON DELETE SET NULL,
        CONSTRAINT fk_product_supplier
            FOREIGN KEY (supplier_id)
            REFERENCES suppliers(id)
            ON DELETE SET NULL,
        CONSTRAINT fk_product_creator
            FOREIGN KEY (created_by)
            REFERENCES users(id)
            ON DELETE SET NULL
    );

    -- สร้างตาราง fresh_categories
    CREATE TABLE IF NOT EXISTS fresh_categories (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        shelf_life_days INTEGER DEFAULT 7,
        storage_condition VARCHAR(50),
        temperature_range VARCHAR(50),
        humidity_range VARCHAR(50),
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );

    -- สร้างตาราง fresh_products
    CREATE TABLE IF NOT EXISTS fresh_products (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        name VARCHAR(200) NOT NULL,
        description TEXT,
        sku VARCHAR(50) UNIQUE,
        barcode VARCHAR(50),
        current_stock DECIMAL(10,3) DEFAULT 0,
        unit VARCHAR(20) DEFAULT 'kg',
        min_stock_quantity DECIMAL(10,3) DEFAULT 0,
        price_per_unit DECIMAL(10,2),
        cost_price_per_unit DECIMAL(10,2),
        image_url TEXT,
        status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'out_of_stock', 'expired')),
        category_id UUID REFERENCES fresh_categories(id) ON DELETE SET NULL,
        supplier_id UUID REFERENCES suppliers(id) ON DELETE SET NULL,
        storage_location VARCHAR(100),
        temperature_zone VARCHAR(50),
        created_by UUID REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );

    -- สร้างตาราง product_lots
    CREATE TABLE IF NOT EXISTS product_lots (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        product_id UUID REFERENCES fresh_products(id) ON DELETE CASCADE,
        lot_number VARCHAR(100) UNIQUE NOT NULL,
        production_date DATE,
        expiry_date DATE NOT NULL,
        quantity DECIMAL(10,3) NOT NULL,
        remaining_quantity DECIMAL(10,3) NOT NULL,
        unit_price DECIMAL(10,2),
        supplier_name VARCHAR(200),
        batch_number VARCHAR(100),
        quality_status VARCHAR(20) DEFAULT 'good' CHECK (quality_status IN ('good', 'damaged', 'expired', 'recalled')),
        storage_condition VARCHAR(50),
        notes TEXT,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );

    -- สร้างตาราง transactions
    CREATE TABLE IF NOT EXISTS transactions (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        product_id UUID NOT NULL,
        type VARCHAR(20) NOT NULL CHECK (type IN ('in', 'out', 'adjustment', 'transfer', 'return')),
        quantity INTEGER NOT NULL CHECK (quantity != 0),
        unit_price DECIMAL(12,2),
        total_amount DECIMAL(12,2),
        reference_number VARCHAR(100),
        notes TEXT,
        location_from VARCHAR(100),
        location_to VARCHAR(100),
        transaction_date TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        created_by UUID,
        approved_by UUID,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_transaction_product
            FOREIGN KEY (product_id)
            REFERENCES products(id)
            ON DELETE CASCADE,
        CONSTRAINT fk_transaction_creator
            FOREIGN KEY (created_by)
            REFERENCES users(id)
            ON DELETE SET NULL,
        CONSTRAINT fk_transaction_approver
            FOREIGN KEY (approved_by)
            REFERENCES users(id)
            ON DELETE SET NULL
    );

    -- สร้างตาราง fresh_stock_transactions
    CREATE TABLE IF NOT EXISTS fresh_stock_transactions (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        product_id UUID REFERENCES fresh_products(id) ON DELETE CASCADE,
        lot_id UUID REFERENCES product_lots(id) ON DELETE CASCADE,
        type VARCHAR(20) NOT NULL CHECK (type IN ('in', 'out', 'adjustment', 'waste', 'damage', 'return')),
        quantity DECIMAL(10,3) NOT NULL,
        unit_price DECIMAL(10,2),
        total_amount DECIMAL(10,2),
        reference_number VARCHAR(50),
        reason VARCHAR(200),
        temperature_at_transaction DECIMAL(5,2),
        notes TEXT,
        transaction_date TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        created_by UUID REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );

    -- สร้างตาราง activity_logs
    CREATE TABLE IF NOT EXISTS activity_logs (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID,
        action VARCHAR(255) NOT NULL,
        description TEXT,
        table_name VARCHAR(100),
        record_id UUID,
        ip_address INET,
        user_agent TEXT,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_activity_user
            FOREIGN KEY (user_id)
            REFERENCES users(id)
            ON DELETE SET NULL
    );

    -- สร้างตาราง notifications
    CREATE TABLE IF NOT EXISTS notifications (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID REFERENCES users(id) ON DELETE CASCADE,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type VARCHAR(50) DEFAULT 'info',
        is_read BOOLEAN DEFAULT false,
        action_url TEXT,
        expires_at TIMESTAMP WITH TIME ZONE,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );

    -- สร้างตาราง system_settings
    CREATE TABLE IF NOT EXISTS system_settings (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        key VARCHAR(100) UNIQUE NOT NULL,
        value TEXT,
        description TEXT,
        category VARCHAR(50),
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    // แยกคำสั่ง SQL และรันทีละคำสั่ง
    $statements = explode(';', $basic_sql);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            $result = pg_query($conn, $statement);
            if ($result) {
                echo "<p style='color: green;'>✓ รันคำสั่ง SQL สำเร็จ</p>";
            } else {
                echo "<p style='color: orange;'>⚠ คำสั่ง SQL อาจมีปัญหา: " . pg_last_error($conn) . "</p>";
            }
        }
    }
} else {
    // รันไฟล์ SQL ที่มีอยู่
    $statements = explode(';', $sql_file);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $result = pg_query($conn, $statement);
            if ($result) {
                echo "<p style='color: green;'>✓ รันคำสั่ง SQL สำเร็จ</p>";
            } else {
                echo "<p style='color: orange;'>⚠ คำสั่ง SQL อาจมีปัญหา: " . pg_last_error($conn) . "</p>";
            }
        }
    }
}

// เพิ่มข้อมูลเริ่มต้น
echo "<h3>เพิ่มข้อมูลเริ่มต้น:</h3>";

// เพิ่มผู้ใช้ Admin (รหัสผ่าน: admin123)
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_query = "
INSERT INTO users (username, password_hash, role) VALUES 
('admin', '$hashed_password', 'admin')
ON CONFLICT (username) DO NOTHING;
";

$result = pg_query($conn, $admin_query);
if ($result) {
    echo "<p style='color: green;'>✓ เพิ่มผู้ดูแลระบบสำเร็จ</p>";
    
    // เพิ่มโปรไฟล์สำหรับ Admin
    $profile_query = "
    INSERT INTO user_profiles (user_id, full_name, email) 
            SELECT u.id, 'ผู้ดูแลระบบ', 'admin@zapshop.com'
    FROM users u WHERE u.username = 'admin'
    ON CONFLICT (email) DO NOTHING;
    ";
    
    $result = pg_query($conn, $profile_query);
    if ($result) {
        echo "<p style='color: green;'>✓ เพิ่มโปรไฟล์ผู้ดูแลระบบสำเร็จ</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ ผู้ดูแลระบบอาจมีอยู่แล้ว</p>";
}

// เพิ่มหมวดหมู่เริ่มต้น
$categories_query = "
INSERT INTO categories (name, description) VALUES 
('อุปกรณ์อิเล็กทรอนิกส์', 'สินค้าอิเล็กทรอนิกส์ต่างๆ'),
('เครื่องเขียน', 'อุปกรณ์เครื่องเขียนและอุปกรณ์สำนักงาน'),
('เสื้อผ้า', 'เสื้อผ้าแฟชั่นสำหรับทุกเพศทุกวัย'),
('อาหาร', 'อาหารและเครื่องดื่ม'),
('เครื่องใช้ไฟฟ้า', 'เครื่องใช้ไฟฟ้าในบ้าน'),
('โทรศัพท์มือถือ', 'สมาร์ทโฟนและอุปกรณ์เสริม'),
('คอมพิวเตอร์', 'แล็ปท็อป เดสก์ท็อป และอุปกรณ์เสริม'),
('กีฬาและออกกำลังกาย', 'อุปกรณ์กีฬาและฟิตเนส'),
('หนังสือและสื่อ', 'หนังสือ นิตยสาร และสื่อการเรียนรู้'),
('ของเล่น', 'ของเล่นสำหรับเด็กและผู้ใหญ่'),
('เครื่องสำอาง', 'เครื่องสำอางและผลิตภัณฑ์ดูแลผิว'),
('สุขภาพและยา', 'ผลิตภัณฑ์สุขภาพและยา'),
('รถยนต์และมอเตอร์ไซค์', 'อะไหล่และอุปกรณ์ยานยนต์'),
('บ้านและสวน', 'เฟอร์นิเจอร์และอุปกรณ์ตกแต่งบ้าน'),
('เครื่องดื่ม', 'เครื่องดื่มและน้ำผลไม้'),
('ขนมและของหวาน', 'ขนม ขนมหวาน และลูกอม'),
('เครื่องครัว', 'อุปกรณ์และเครื่องใช้ในครัว'),
('เครื่องแต่งกาย', 'เสื้อผ้า รองเท้า และกระเป๋า'),
('เครื่องประดับ', 'เครื่องประดับและอัญมณี'),
('อุปกรณ์การเรียน', 'อุปกรณ์สำหรับนักเรียนและนักศึกษา'),
('อุปกรณ์สำนักงาน', 'เครื่องใช้สำนักงานและอุปกรณ์คอมพิวเตอร์'),
('อุปกรณ์ก่อสร้าง', 'เครื่องมือและวัสดุก่อสร้าง'),
('อุปกรณ์เกษตร', 'เครื่องมือและอุปกรณ์การเกษตร'),
('อุปกรณ์ช่าง', 'เครื่องมือช่างและอุปกรณ์ซ่อมบำรุง'),
('อุปกรณ์แพทย์', 'อุปกรณ์ทางการแพทย์และสุขภาพ'),
('อุปกรณ์ดนตรี', 'เครื่องดนตรีและอุปกรณ์เสียง'),
('อุปกรณ์ถ่ายภาพ', 'กล้องถ่ายรูปและอุปกรณ์ถ่ายภาพ'),
('อุปกรณ์เดินทาง', 'กระเป๋าเดินทางและอุปกรณ์ท่องเที่ยว'),
('อุปกรณ์เลี้ยงสัตว์', 'อาหารและอุปกรณ์สำหรับสัตว์เลี้ยง'),
('อุปกรณ์ทำความสะอาด', 'ผลิตภัณฑ์ทำความสะอาดและเครื่องใช้'),
('อุปกรณ์จัดเก็บ', 'กล่อง จัดเก็บ และอุปกรณ์จัดระเบียบ')
ON CONFLICT (name) DO NOTHING;
";

$result = pg_query($conn, $categories_query);
if ($result) {
    echo "<p style='color: green;'>✓ เพิ่มหมวดหมู่สำเร็จ</p>";
} else {
    echo "<p style='color: orange;'>⚠ หมวดหมู่อาจมีอยู่แล้ว</p>";
}

// เพิ่มหมวดหมู่สินค้าของสด
$fresh_categories_query = "
INSERT INTO fresh_categories (name, description, shelf_life_days, storage_condition) VALUES 
('ผักสด', 'ผักสดและผักใบเขียว', 7, 'แช่เย็น'),
('ผลไม้', 'ผลไม้สดและผลไม้แช่เย็น', 14, 'แช่เย็น'),
('เนื้อสัตว์', 'เนื้อสัตว์สดและแช่เย็น', 5, 'แช่เย็น'),
('ปลาและอาหารทะเล', 'ปลาสดและอาหารทะเล', 3, 'แช่เย็น'),
('นมและผลิตภัณฑ์นม', 'นม โยเกิร์ต ชีส', 7, 'แช่เย็น')
ON CONFLICT (name) DO NOTHING;
";

$result = pg_query($conn, $fresh_categories_query);
if ($result) {
    echo "<p style='color: green;'>✓ เพิ่มหมวดหมู่สินค้าของสดสำเร็จ</p>";
} else {
    echo "<p style='color: orange;'>⚠ หมวดหมู่สินค้าของสดอาจมีอยู่แล้ว</p>";
}

// เพิ่มการตั้งค่าระบบ
$settings_query = "
INSERT INTO system_settings (key, value, description, category) VALUES 
('company_name', 'บริษัท จัดการสต็อกสินค้า จำกัด', 'ชื่อบริษัท', 'general'),
('company_address', '123 ถนนสุขุมวิท กรุงเทพฯ 10110', 'ที่อยู่บริษัท', 'general'),
('currency', 'THB', 'สกุลเงินที่ใช้', 'general'),
('timezone', 'Asia/Bangkok', 'เขตเวลา', 'general'),
('low_stock_alert_days', '3', 'จำนวนวันที่แจ้งเตือนสินค้าต่ำ', 'alerts'),
('expiry_alert_days', '7', 'จำนวนวันก่อนหมดอายุที่แจ้งเตือน', 'alerts')
ON CONFLICT (key) DO NOTHING;
";

$result = pg_query($conn, $settings_query);
if ($result) {
    echo "<p style='color: green;'>✓ เพิ่มการตั้งค่าระบบสำเร็จ</p>";
} else {
    echo "<p style='color: orange;'>⚠ การตั้งค่าระบบอาจมีอยู่แล้ว</p>";
}

// ตรวจสอบข้อมูล
echo "<h3>ตรวจสอบข้อมูล:</h3>";

$result = pg_query($conn, "SELECT COUNT(*) as count FROM users");
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "<p>จำนวนผู้ใช้: " . $row['count'] . " คน</p>";
}

$result = pg_query($conn, "SELECT COUNT(*) as count FROM categories");
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "<p>จำนวนหมวดหมู่: " . $row['count'] . " หมวด</p>";
}

$result = pg_query($conn, "SELECT COUNT(*) as count FROM fresh_categories");
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "<p>จำนวนหมวดหมู่สินค้าของสด: " . $row['count'] . " หมวด</p>";
}

$result = pg_query($conn, "SELECT COUNT(*) as count FROM system_settings");
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "<p>จำนวนการตั้งค่าระบบ: " . $row['count'] . " รายการ</p>";
}

// แสดงโครงสร้างตาราง
echo "<h3>โครงสร้างตาราง:</h3>";

$result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name");
if ($result) {
    echo "<p><strong>ตารางทั้งหมด:</strong></p>";
    echo "<ul>";
    while ($row = pg_fetch_assoc($result)) {
        echo "<li>" . $row['table_name'] . "</li>";
    }
    echo "</ul>";
}

pg_close($conn);

echo "<h3>เสร็จสิ้น!</h3>";
echo "<p style='color: green; font-weight: bold;'>ฐานข้อมูล PostgreSQL ใหม่ถูกสร้างเรียบร้อยแล้ว</p>";
echo "<p><strong>ข้อมูลเข้าสู่ระบบ:</strong></p>";
echo "<ul>";
echo "<li>Username: admin</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";
echo "<p><a href='user-login.php' style='color: blue; font-weight: bold;'>ทดสอบการเข้าสู่ระบบ</a></p>";
?>
