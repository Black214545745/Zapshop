<?php
include 'config.php';

try {
    $conn = getConnection();
    
    // สร้างตาราง orders
    $create_orders_table = "
    CREATE TABLE IF NOT EXISTS orders (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        user_id UUID REFERENCES users(id) ON DELETE CASCADE,
        fullname VARCHAR(255) NOT NULL,
        tel VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        address TEXT NOT NULL,
        grand_total DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) NOT NULL,
        payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    $result = pg_query($conn, $create_orders_table);
    if (!$result) {
        throw new Exception("Error creating orders table: " . pg_last_error($conn));
    }
    
    // สร้างตาราง order_details
    $create_order_details_table = "
    CREATE TABLE IF NOT EXISTS order_details (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        order_id UUID REFERENCES orders(id) ON DELETE CASCADE,
        product_id UUID REFERENCES products(id) ON DELETE CASCADE,
        product_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INTEGER NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    $result = pg_query($conn, $create_order_details_table);
    if (!$result) {
        throw new Exception("Error creating order_details table: " . pg_last_error($conn));
    }
    
    // สร้าง Indexes
    $create_indexes = "
    CREATE INDEX IF NOT EXISTS idx_orders_user_id ON orders(user_id);
    CREATE INDEX IF NOT EXISTS idx_orders_order_date ON orders(order_date);
    CREATE INDEX IF NOT EXISTS idx_orders_status ON orders(status);
    CREATE INDEX IF NOT EXISTS idx_order_details_order_id ON order_details(order_id);
    CREATE INDEX IF NOT EXISTS idx_order_details_product_id ON order_details(product_id);
    ";
    
    $result = pg_query($conn, $create_indexes);
    if (!$result) {
        throw new Exception("Error creating indexes: " . pg_last_error($conn));
    }
    
    // สร้าง Trigger สำหรับอัปเดต updated_at
    $create_trigger = "
    CREATE OR REPLACE FUNCTION update_orders_updated_at()
    RETURNS TRIGGER AS $$
    BEGIN
        NEW.updated_at = CURRENT_TIMESTAMP;
        RETURN NEW;
    END;
    $$ language 'plpgsql';
    
    DROP TRIGGER IF EXISTS trigger_update_orders_updated_at ON orders;
    CREATE TRIGGER trigger_update_orders_updated_at
        BEFORE UPDATE ON orders
        FOR EACH ROW
        EXECUTE FUNCTION update_orders_updated_at();
    ";
    
    $result = pg_query($conn, $create_trigger);
    if (!$result) {
        throw new Exception("Error creating trigger: " . pg_last_error($conn));
    }
    
    pg_close($conn);
    
    echo "<h2>✅ สร้างตารางสำเร็จ!</h2>";
    echo "<p>ตาราง orders และ order_details ได้ถูกสร้างเรียบร้อยแล้ว</p>";
    echo "<p><a href='index.php'>กลับหน้าหลัก</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ เกิดข้อผิดพลาด!</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p><a href='index.php'>กลับหน้าหลัก</a></p>";
}
?>
