<?php
/**
 * Recreate Orders Table
 * ลบตาราง orders เดิมและสร้างใหม่ตามที่โค้ดคาดหวัง
 */

require_once 'config.php';

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔄 สร้างตาราง Orders ใหม่</h2>";

try {
    $conn = getConnection();
    if (!$conn) {
        throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
    }
    
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br><br>";
    
    // ตรวจสอบตาราง orders ที่มีอยู่
    $checkTableQuery = "
        SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_name = 'orders'
        )
    ";
    
    $checkTableResult = pg_query($conn, $checkTableQuery);
    if (!$checkTableResult) {
        throw new Exception('ไม่สามารถตรวจสอบตาราง orders ได้: ' . pg_last_error($conn));
    }
    
    $tableExists = pg_fetch_result($checkTableResult, 0, 0) === 't';
    
    if ($tableExists) {
        echo "📋 ตาราง orders มีอยู่แล้ว<br>";
        
        // แสดงโครงสร้างปัจจุบัน
        $checkColumnsQuery = "
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns 
            WHERE table_name = 'orders'
            ORDER BY ordinal_position
        ";
        
        $checkColumnsResult = pg_query($conn, $checkColumnsQuery);
        if ($checkColumnsResult) {
            echo "<h3>โครงสร้างปัจจุบัน:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>คอลัมน์</th><th>ประเภทข้อมูล</th><th>Null ได้</th><th>ค่าเริ่มต้น</th></tr>";
            
            while ($row = pg_fetch_assoc($checkColumnsResult)) {
                echo "<tr>";
                echo "<td>{$row['column_name']}</td>";
                echo "<td>{$row['data_type']}</td>";
                echo "<td>{$row['is_nullable']}</td>";
                echo "<td>{$row['column_default']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }
        
        // ลบตาราง orders เดิม
        echo "🗑️ กำลังลบตาราง orders เดิม...<br>";
        
        // ลบตาราง order_items ก่อน (ถ้ามี)
        $dropOrderItemsQuery = "DROP TABLE IF EXISTS order_items CASCADE";
        $dropOrderItemsResult = pg_query($conn, $dropOrderItemsQuery);
        if ($dropOrderItemsResult) {
            echo "✅ ลบตาราง order_items สำเร็จ<br>";
        }
        
        // ลบตาราง orders
        $dropOrdersQuery = "DROP TABLE IF EXISTS orders CASCADE";
        $dropOrdersResult = pg_query($conn, $dropOrdersQuery);
        if ($dropOrdersResult) {
            echo "✅ ลบตาราง orders สำเร็จ<br>";
        } else {
            throw new Exception('ไม่สามารถลบตาราง orders ได้: ' . pg_last_error($conn));
        }
        
    } else {
        echo "📋 ตาราง orders ไม่มีอยู่<br>";
    }
    
    // สร้างตาราง orders ใหม่
    echo "<br>🆕 กำลังสร้างตาราง orders ใหม่...<br>";
    
    $createOrdersQuery = "
        CREATE TABLE orders (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            shipping_address TEXT NOT NULL,
            shipping_phone VARCHAR(20) NOT NULL,
            shipping_email VARCHAR(100) NOT NULL,
            order_status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $createOrdersResult = pg_query($conn, $createOrdersQuery);
    if (!$createOrdersResult) {
        throw new Exception('ไม่สามารถสร้างตาราง orders ได้: ' . pg_last_error($conn));
    }
    echo "✅ สร้างตาราง orders สำเร็จ<br>";
    
    // สร้างตาราง order_items
    echo "🆕 กำลังสร้างตาราง order_items...<br>";
    
    $createOrderItemsQuery = "
        CREATE TABLE order_items (
            id SERIAL PRIMARY KEY,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INTEGER NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $createOrderItemsResult = pg_query($conn, $createOrderItemsQuery);
    if (!$createOrderItemsResult) {
        throw new Exception('ไม่สามารถสร้างตาราง order_items ได้: ' . pg_last_error($conn));
    }
    echo "✅ สร้างตาราง order_items สำเร็จ<br>";
    
    // สร้างตาราง payments
    echo "🆕 กำลังสร้างตาราง payments...<br>";
    
    $createPaymentsQuery = "
        CREATE TABLE payments (
            id SERIAL PRIMARY KEY,
            order_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_status VARCHAR(50) DEFAULT 'pending',
            payment_details TEXT,
            promptpay_id VARCHAR(50),
            qr_code_generated BOOLEAN DEFAULT FALSE,
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";
    
    $createPaymentsResult = pg_query($conn, $createPaymentsQuery);
    if (!$createPaymentsResult) {
        throw new Exception('ไม่สามารถสร้างตาราง payments ได้: ' . pg_last_error($conn));
    }
    echo "✅ สร้างตาราง payments สำเร็จ<br>";
    
    // ตรวจสอบโครงสร้างตารางใหม่
    echo "<br><h3>📋 โครงสร้างตาราง orders ใหม่:</h3>";
    $checkNewColumnsQuery = "
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'orders'
        ORDER BY ordinal_position
    ";
    
    $checkNewColumnsResult = pg_query($conn, $checkNewColumnsQuery);
    if ($checkNewColumnsResult) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>คอลัมน์</th><th>ประเภทข้อมูล</th><th>Null ได้</th><th>ค่าเริ่มต้น</th></tr>";
        
        while ($row = pg_fetch_assoc($checkNewColumnsResult)) {
            echo "<tr>";
            echo "<td>{$row['column_name']}</td>";
            echo "<td>{$row['data_type']}</td>";
            echo "<td>{$row['is_nullable']}</td>";
            echo "<td>{$row['column_default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    pg_close($conn);
    
    echo "<br><hr>";
    echo "<h3>✅ การสร้างตารางเสร็จสิ้น!</h3>";
    echo "ตาราง orders, order_items, และ payments พร้อมใช้งานแล้ว<br>";
    echo "คุณสามารถทดสอบการสร้างคำสั่งซื้อได้แล้ว<br>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<br><a href='debug_payment.php' class='btn btn-primary'>ตรวจสอบระบบ</a>";
echo "<a href='test_simple_order.php' class='btn btn-success'>ทดสอบการสร้างคำสั่งซื้อ</a>";
echo "<a href='checkout.php' class='btn btn-secondary'>กลับไปหน้าชำระเงิน</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; margin-top: 20px; }
.btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }
hr { border: 1px solid #ddd; margin: 20px 0; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f8f9fa; }
</style>
