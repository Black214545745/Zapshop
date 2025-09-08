<?php
echo "<h2>ตั้งค่าฐานข้อมูล PostgreSQL</h2>";

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

// สร้างตาราง users
$sql_users = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role VARCHAR(10) DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

$result = pg_query($conn, $sql_users);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง users สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง users ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้างตาราง categories
$sql_categories = "
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

$result = pg_query($conn, $sql_categories);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง categories สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง categories ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้างตาราง products
$sql_products = "
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INTEGER DEFAULT 0,
    category_id INTEGER,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);
";

$result = pg_query($conn, $sql_products);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง products สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง products ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// เพิ่มข้อมูลตัวอย่าง
echo "<h3>เพิ่มข้อมูลตัวอย่าง:</h3>";

// เพิ่มหมวดหมู่
$sql_insert_categories = "
INSERT INTO categories (name, description) VALUES 
('เครื่องดื่ม', 'เครื่องดื่มต่างๆ'),
('อาหาร', 'อาหารสำเร็จรูป'),
('ของใช้ในบ้าน', 'อุปกรณ์และของใช้ในบ้าน'),
('เครื่องสำอาง', 'เครื่องสำอางและผลิตภัณฑ์ดูแลผิว')
ON CONFLICT DO NOTHING;
";

$result = pg_query($conn, $sql_insert_categories);
if ($result) {
    echo "<p style='color: green;'>✓ เพิ่มหมวดหมู่สำเร็จ</p>";
} else {
    echo "<p style='color: orange;'>⚠ หมวดหมู่อาจมีอยู่แล้ว</p>";
}

// เพิ่มสินค้า
$sql_insert_products = "
INSERT INTO products (name, description, price, stock_quantity, category_id, image_url) VALUES 
('กาแฟดำ', 'กาแฟดำร้อน 100%', 25.00, 100, 1, 'coffee.jpg'),
('น้ำส้มคั้น', 'น้ำส้มคั้นสด 100%', 30.00, 50, 1, 'orange-juice.jpg'),
('ขนมปัง', 'ขนมปังสดใหม่', 15.00, 200, 2, 'bread.jpg'),
('สบู่', 'สบู่ล้างมือ', 45.00, 80, 3, 'soap.jpg'),
('ครีมบำรุงผิว', 'ครีมบำรุงผิวหน้า', 299.00, 30, 4, 'cream.jpg')
ON CONFLICT DO NOTHING;
";

$result = pg_query($conn, $sql_insert_products);
if ($result) {
    echo "<p style='color: green;'>✓ เพิ่มสินค้าสำเร็จ</p>";
} else {
    echo "<p style='color: orange;'>⚠ สินค้าอาจมีอยู่แล้ว</p>";
}

// เพิ่มผู้ดูแลระบบ (รหัสผ่าน: password)
$hashed_password = password_hash('password', PASSWORD_DEFAULT);
$sql_insert_admin = "
INSERT INTO users (username, email, password, full_name, role) VALUES 
('admin', 'admin@zapshop.com', '$hashed_password', 'ผู้ดูแลระบบ', 'admin')
ON CONFLICT DO NOTHING;
";

$result = pg_query($conn, $sql_insert_admin);
if ($result) {
    echo "<p style='color: green;'>✓ เพิ่มผู้ดูแลระบบสำเร็จ</p>";
    echo "<p><strong>ข้อมูลเข้าสู่ระบบ:</strong></p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: password</li>";
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠ ผู้ดูแลระบบอาจมีอยู่แล้ว</p>";
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

$result = pg_query($conn, "SELECT COUNT(*) as count FROM products");
if ($result) {
    $row = pg_fetch_assoc($result);
    echo "<p>จำนวนสินค้า: " . $row['count'] . " รายการ</p>";
}

pg_close($conn);

echo "<h3>เสร็จสิ้น!</h3>";
echo "<p>ฐานข้อมูลพร้อมใช้งานแล้ว</p>";
echo "<p><a href='user-login.php'>ทดสอบการเข้าสู่ระบบ</a></p>";
?>
