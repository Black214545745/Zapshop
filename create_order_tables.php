<?php
include 'config.php';

$conn = getConnection();

echo "<h2>กำลังสร้างตารางสำหรับระบบคำสั่งซื้อ...</h2>";

// สร้างตาราง orders
$create_orders_table = "
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    shipping_address TEXT,
    payment_method VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (pg_query($conn, $create_orders_table)) {
    echo "<p style='color: green;'>✓ สร้างตาราง orders สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการสร้างตาราง orders</p>";
}

// สร้างตาราง order_items
$create_order_items_table = "
CREATE TABLE IF NOT EXISTS order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INTEGER NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (pg_query($conn, $create_order_items_table)) {
    echo "<p style='color: green;'>✓ สร้างตาราง order_items สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการสร้างตาราง order_items</p>";
}

// สร้าง Foreign Key constraints
$add_foreign_keys = "
ALTER TABLE orders ADD CONSTRAINT IF NOT EXISTS fk_orders_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE order_items ADD CONSTRAINT IF NOT EXISTS fk_order_items_order_id 
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

ALTER TABLE order_items ADD CONSTRAINT IF NOT EXISTS fk_order_items_product_id 
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;
";

if (pg_query($conn, $add_foreign_keys)) {
    echo "<p style='color: green;'>✓ เพิ่ม Foreign Key constraints สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการเพิ่ม Foreign Key constraints</p>";
}

// สร้าง Indexes สำหรับประสิทธิภาพ
$create_indexes = "
CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders(user_id);
CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at);
CREATE INDEX IF NOT EXISTS idx_order_items_order_id ON order_items(order_id);
CREATE INDEX IF NOT EXISTS idx_order_items_product_id ON order_items(product_id);
";

if (pg_query($conn, $create_indexes)) {
    echo "<p style='color: green;'>✓ สร้าง Indexes สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการสร้าง Indexes</p>";
}

// เพิ่มข้อมูลตัวอย่าง (ถ้าต้องการ)
$sample_orders = "
INSERT INTO orders (user_id, status, total_amount, shipping_address, payment_method) VALUES
(1, 'pending', 1500.00, '123 ถนนสุขุมวิท, กรุงเทพฯ 10110', 'โอนเงิน'),
(1, 'processing', 2300.00, '123 ถนนสุขุมวิท, กรุงเทพฯ 10110', 'บัตรเครดิต'),
(1, 'delivered', 800.00, '123 ถนนสุขุมวิท, กรุงเทพฯ 10110', 'เงินสด')
ON CONFLICT DO NOTHING;
";

if (pg_query($conn, $sample_orders)) {
    echo "<p style='color: green;'>✓ เพิ่มข้อมูลตัวอย่าง orders สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการเพิ่มข้อมูลตัวอย่าง orders</p>";
}

$sample_order_items = "
INSERT INTO order_items (order_id, product_id, product_name, quantity, price, image_url) VALUES
(1, 1, 'สินค้าตัวอย่าง 1', 2, 750.00, 'sample1.jpg'),
(2, 2, 'สินค้าตัวอย่าง 2', 1, 2300.00, 'sample2.jpg'),
(3, 3, 'สินค้าตัวอย่าง 3', 1, 800.00, 'sample3.jpg')
ON CONFLICT DO NOTHING;
";

if (pg_query($conn, $sample_order_items)) {
    echo "<p style='color: green;'>✓ เพิ่มข้อมูลตัวอย่าง order_items สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาดในการเพิ่มข้อมูลตัวอย่าง order_items</p>";
}

pg_close($conn);

echo "<h3>เสร็จสิ้น!</h3>";
echo "<p>ตารางสำหรับระบบคำสั่งซื้อได้ถูกสร้างเรียบร้อยแล้ว</p>";
echo "<p><a href='orders.php'>คลิกที่นี่เพื่อไปยังหน้าประวัติการสั่งซื้อ</a></p>";
?>
