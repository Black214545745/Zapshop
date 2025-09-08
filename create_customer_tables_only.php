<?php
session_start();
include 'config.php';

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

echo "<h2>สร้างตารางสำหรับลูกค้า (แยกจากตารางพนักงาน)</h2>";

// สร้างตาราง customers (สำหรับลูกค้าที่ซื้อสินค้า)
$sql_customers = "
CREATE TABLE IF NOT EXISTS customers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender VARCHAR(10) CHECK (gender IN ('male', 'female', 'other')),
    is_active BOOLEAN DEFAULT true,
    is_verified BOOLEAN DEFAULT false,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_token_expires TIMESTAMP,
    last_login TIMESTAMP,
    login_attempts INTEGER DEFAULT 0,
    locked_until TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
";

$result = pg_query($conn, $sql_customers);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง customers สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง customers ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้างตาราง customer_profiles (สำหรับข้อมูลเพิ่มเติมของลูกค้า)
$sql_customer_profiles = "
CREATE TABLE IF NOT EXISTS customer_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    avatar_url TEXT,
    bio TEXT,
    preferences JSONB,
    newsletter_subscription BOOLEAN DEFAULT false,
    sms_notifications BOOLEAN DEFAULT true,
    email_notifications BOOLEAN DEFAULT true,
    language VARCHAR(10) DEFAULT 'th',
    timezone VARCHAR(50) DEFAULT 'Asia/Bangkok',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
";

$result = pg_query($conn, $sql_customer_profiles);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง customer_profiles สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง customer_profiles ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้างตาราง customer_addresses (ที่อยู่ลูกค้า)
$sql_customer_addresses = "
CREATE TABLE IF NOT EXISTS customer_addresses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    address_type VARCHAR(20) DEFAULT 'home' CHECK (address_type IN ('home', 'work', 'billing', 'shipping')),
    is_default BOOLEAN DEFAULT false,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    company VARCHAR(200),
    address_line_1 VARCHAR(255) NOT NULL,
    address_line_2 VARCHAR(255),
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Thailand',
    phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);
";

$result = pg_query($conn, $sql_customer_addresses);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง customer_addresses สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง customer_addresses ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้างตาราง customer_wishlist (รายการสินค้าที่ชอบ)
$sql_customer_wishlist = "
CREATE TABLE IF NOT EXISTS customer_wishlist (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(customer_id, product_id)
);
";

$result = pg_query($conn, $sql_customer_wishlist);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง customer_wishlist สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง customer_wishlist ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้างตาราง customer_reviews (รีวิวสินค้า)
$sql_customer_reviews = "
CREATE TABLE IF NOT EXISTS customer_reviews (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    order_id UUID REFERENCES orders(id) ON DELETE SET NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT false,
    is_approved BOOLEAN DEFAULT false,
    helpful_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(customer_id, product_id, order_id)
);
";

$result = pg_query($conn, $sql_customer_reviews);
if ($result) {
    echo "<p style='color: green;'>✓ สร้างตาราง customer_reviews สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้างตาราง customer_reviews ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้าง Indexes
$indexes = [
    "CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);",
    "CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone);",
    "CREATE INDEX IF NOT EXISTS idx_customers_created_at ON customers(created_at);",
    "CREATE INDEX IF NOT EXISTS idx_customer_profiles_customer_id ON customer_profiles(customer_id);",
    "CREATE INDEX IF NOT EXISTS idx_customer_addresses_customer_id ON customer_addresses(customer_id);",
    "CREATE INDEX IF NOT EXISTS idx_customer_addresses_type ON customer_addresses(address_type);",
    "CREATE INDEX IF NOT EXISTS idx_customer_wishlist_customer_id ON customer_wishlist(customer_id);",
    "CREATE INDEX IF NOT EXISTS idx_customer_wishlist_product_id ON customer_wishlist(product_id);",
    "CREATE INDEX IF NOT EXISTS idx_customer_reviews_customer_id ON customer_reviews(customer_id);",
    "CREATE INDEX IF NOT EXISTS idx_customer_reviews_product_id ON customer_reviews(product_id);",
    "CREATE INDEX IF NOT EXISTS idx_customer_reviews_rating ON customer_reviews(rating);"
];

echo "<h3>สร้าง Indexes:</h3>";
foreach ($indexes as $index) {
    $result = pg_query($conn, $index);
    if ($result) {
        echo "<p style='color: green;'>✓ สร้าง Index สำเร็จ</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Index อาจมีอยู่แล้ว: " . pg_last_error($conn) . "</p>";
    }
}

// เพิ่มข้อมูลตัวอย่างลูกค้า
echo "<h3>เพิ่มข้อมูลตัวอย่างลูกค้า:</h3>";

$sample_customers = [
    [
        'email' => 'customer1@example.com',
        'password' => 'password123',
        'first_name' => 'สมชาย',
        'last_name' => 'ใจดี',
        'phone' => '0812345678',
        'date_of_birth' => '1990-05-15',
        'gender' => 'male'
    ],
    [
        'email' => 'customer2@example.com',
        'password' => 'password123',
        'first_name' => 'สมหญิง',
        'last_name' => 'รักดี',
        'phone' => '0823456789',
        'date_of_birth' => '1985-08-22',
        'gender' => 'female'
    ],
    [
        'email' => 'customer3@example.com',
        'password' => 'password123',
        'first_name' => 'John',
        'last_name' => 'Smith',
        'phone' => '0834567890',
        'date_of_birth' => '1992-12-10',
        'gender' => 'male'
    ]
];

foreach ($sample_customers as $customer) {
    $hashed_password = password_hash($customer['password'], PASSWORD_DEFAULT);
    
    $insert_query = "
        INSERT INTO customers (email, password_hash, first_name, last_name, phone, date_of_birth, gender, is_verified) 
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8)
        ON CONFLICT (email) DO NOTHING
    ";
    
    $result = pg_query_params($conn, $insert_query, [
        $customer['email'],
        $hashed_password,
        $customer['first_name'],
        $customer['last_name'],
        $customer['phone'],
        $customer['date_of_birth'],
        $customer['gender'],
        true
    ]);
    
    if ($result) {
        echo "<p style='color: green;'>✓ เพิ่มลูกค้า: " . $customer['first_name'] . " " . $customer['last_name'] . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ ลูกค้าอาจมีอยู่แล้ว: " . $customer['email'] . "</p>";
    }
}

pg_close($conn);

echo "<h3>✅ สร้างตารางสำหรับลูกค้าเสร็จสิ้น!</h3>";
echo "<p><strong>ตารางที่สร้างใหม่:</strong></p>";
echo "<ul>";
echo "<li><strong>customers</strong> - ข้อมูลหลักของลูกค้า (อีเมล, รหัสผ่าน, ชื่อ-นามสกุล, เบอร์โทร, วันเกิด, เพศ)</li>";
echo "<li><strong>customer_profiles</strong> - ข้อมูลเพิ่มเติมของลูกค้า (รูปโปรไฟล์, ความชอบ, การตั้งค่าการแจ้งเตือน)</li>";
echo "<li><strong>customer_addresses</strong> - ที่อยู่ของลูกค้า (บ้าน, ที่ทำงาน, ที่จัดส่ง, ที่เรียกเก็บเงิน)</li>";
echo "<li><strong>customer_wishlist</strong> - รายการสินค้าที่ลูกค้าชอบ</li>";
echo "<li><strong>customer_reviews</strong> - รีวิวสินค้าจากลูกค้า</li>";
echo "</ul>";

echo "<p><strong>ตารางเดิม (ไม่เปลี่ยนแปลง):</strong></p>";
echo "<ul>";
echo "<li><strong>users</strong> - ข้อมูลพนักงาน (admin, user)</li>";
echo "<li><strong>user_profiles</strong> - ข้อมูลเพิ่มเติมของพนักงาน</li>";
echo "</ul>";

echo "<p><strong>ข้อมูลตัวอย่างลูกค้า:</strong></p>";
echo "<ul>";
echo "<li><strong>customer1@example.com</strong> / password123 (สมชาย ใจดี)</li>";
echo "<li><strong>customer2@example.com</strong> / password123 (สมหญิง รักดี)</li>";
echo "<li><strong>customer3@example.com</strong> / password123 (John Smith)</li>";
echo "</ul>";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างตารางลูกค้า</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h2, h3 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        p {
            margin: 10px 0;
            padding: 8px;
            border-radius: 4px;
        }
        ul {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        li {
            margin: 10px 0;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div style="background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h1 style="text-align: center; color: #007bff;">🏪 Zap Shop - สร้างตารางลูกค้า</h1>
        
        <div style="margin-top: 30px;">
            <h3>📋 สรุปการสร้างตาราง</h3>
            <p>ระบบได้สร้างตารางใหม่สำหรับจัดการข้อมูลลูกค้าเรียบร้อยแล้ว</p>
            <p><strong>หมายเหตุ:</strong> ตาราง <code>users</code> และ <code>user_profiles</code> ยังคงใช้เก็บข้อมูลพนักงานตามเดิม</p>
            
            <h3>🔗 ลิงก์ที่เกี่ยวข้อง</h3>
            <ul>
                <li><a href="customer-login.php">🔐 เข้าสู่ระบบลูกค้า</a></li>
                <li><a href="customer-register.php">📝 สมัครสมาชิกลูกค้า</a></li>
                <li><a href="admin-dashboard.php">📊 Admin Dashboard</a></li>
                <li><a href="index.php">🏠 หน้าแรก</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
