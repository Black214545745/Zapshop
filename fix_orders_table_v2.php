<?php
/**
 * Fix Orders Table Structure V2
 * แก้ไขโครงสร้างตาราง orders ให้ตรงกับโค้ดที่คาดหวัง
 */

require_once 'config.php';

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 แก้ไขโครงสร้างตาราง Orders V2</h2>";

try {
    $conn = getConnection();
    if (!$conn) {
        throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
    }
    
    echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br><br>";
    
    // ตรวจสอบโครงสร้างตาราง orders ปัจจุบัน
    $checkColumnsQuery = "
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'orders'
        ORDER BY ordinal_position
    ";
    
    $checkColumnsResult = pg_query($conn, $checkColumnsQuery);
    if (!$checkColumnsResult) {
        throw new Exception('ไม่สามารถตรวจสอบคอลัมน์ได้: ' . pg_last_error($conn));
    }
    
    echo "<h3>📋 โครงสร้างตาราง orders ปัจจุบัน:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>คอลัมน์</th><th>ประเภทข้อมูล</th><th>Null ได้</th><th>ค่าเริ่มต้น</th></tr>";
    
    $existingColumns = [];
    while ($row = pg_fetch_assoc($checkColumnsResult)) {
        $existingColumns[] = $row['column_name'];
        echo "<tr>";
        echo "<td>{$row['column_name']}</td>";
        echo "<td>{$row['data_type']}</td>";
        echo "<td>{$row['is_nullable']}</td>";
        echo "<td>{$row['column_default']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // ตรวจสอบและเพิ่มคอลัมน์ที่จำเป็น
    $requiredColumns = [
        'order_number' => ['type' => 'VARCHAR(50)', 'nullable' => 'NOT NULL', 'default' => '', 'unique' => true],
        'total_amount' => ['type' => 'DECIMAL(10,2)', 'nullable' => 'NOT NULL', 'default' => '', 'unique' => false],
        'shipping_address' => ['type' => 'TEXT', 'nullable' => 'NOT NULL', 'default' => '', 'unique' => false],
        'shipping_phone' => ['type' => 'VARCHAR(20)', 'nullable' => 'NOT NULL', 'default' => '', 'unique' => false],
        'shipping_email' => ['type' => 'VARCHAR(100)', 'nullable' => 'NOT NULL', 'default' => '', 'unique' => false],
        'order_status' => ['type' => 'VARCHAR(50)', 'nullable' => 'DEFAULT', 'default' => "'pending'", 'unique' => false]
    ];
    
    echo "<h3>🔧 เพิ่มคอลัมน์ที่ขาดหายไป:</h3>";
    
    foreach ($requiredColumns as $columnName => $columnInfo) {
        if (!in_array($columnName, $existingColumns)) {
            echo "📝 เพิ่มคอลัมน์: $columnName<br>";
            
            $addColumnQuery = "ALTER TABLE orders ADD COLUMN $columnName {$columnInfo['type']}";
            
            if ($columnInfo['nullable'] === 'NOT NULL') {
                $addColumnQuery .= " NOT NULL";
            }
            
            if ($columnInfo['nullable'] === 'DEFAULT' && !empty($columnInfo['default'])) {
                $addColumnQuery .= " DEFAULT {$columnInfo['default']}";
            }
            
            $addColumnResult = pg_query($conn, $addColumnQuery);
            if (!$addColumnResult) {
                echo "❌ ไม่สามารถเพิ่มคอลัมน์ $columnName ได้: " . pg_last_error($conn) . "<br>";
            } else {
                echo "✅ เพิ่มคอลัมน์ $columnName สำเร็จ<br>";
                
                // เพิ่ม unique constraint สำหรับ order_number
                if ($columnInfo['unique'] && $columnName === 'order_number') {
                    try {
                        $addUniqueQuery = "ALTER TABLE orders ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number)";
                        pg_query($conn, $addUniqueQuery);
                        echo "✅ เพิ่ม unique constraint สำหรับ order_number สำเร็จ<br>";
                    } catch (Exception $e) {
                        echo "⚠️ ไม่สามารถเพิ่ม unique constraint ได้ (อาจมีอยู่แล้ว): " . $e->getMessage() . "<br>";
                    }
                }
            }
        } else {
            echo "✅ คอลัมน์ $columnName มีอยู่แล้ว<br>";
        }
    }
    
    // อัปเดตข้อมูลในคอลัมน์ใหม่ให้ตรงกับข้อมูลเดิม
    echo "<br><h3>🔄 อัปเดตข้อมูลในคอลัมน์ใหม่:</h3>";
    
    // อัปเดต shipping_address จาก address
    if (in_array('address', $existingColumns) && in_array('shipping_address', $existingColumns)) {
        $updateAddressQuery = "UPDATE orders SET shipping_address = address WHERE shipping_address IS NULL";
        $updateAddressResult = pg_query($conn, $updateAddressQuery);
        if ($updateAddressResult) {
            echo "✅ อัปเดต shipping_address จาก address สำเร็จ<br>";
        } else {
            echo "⚠️ ไม่สามารถอัปเดต shipping_address ได้: " . pg_last_error($conn) . "<br>";
        }
    }
    
    // อัปเดต shipping_phone จาก tel
    if (in_array('tel', $existingColumns) && in_array('shipping_phone', $existingColumns)) {
        $updatePhoneQuery = "UPDATE orders SET shipping_phone = tel WHERE shipping_phone IS NULL";
        $updatePhoneResult = pg_query($conn, $updatePhoneQuery);
        if ($updatePhoneResult) {
            echo "✅ อัปเดต shipping_phone จาก tel สำเร็จ<br>";
        } else {
            echo "⚠️ ไม่สามารถอัปเดต shipping_phone ได้: " . pg_last_error($conn) . "<br>";
        }
    }
    
    // อัปเดต shipping_email จาก email
    if (in_array('email', $existingColumns) && in_array('shipping_email', $existingColumns)) {
        $updateEmailQuery = "UPDATE orders SET shipping_email = email WHERE shipping_email IS NULL";
        $updateEmailResult = pg_query($conn, $updateEmailQuery);
        if ($updateEmailResult) {
            echo "✅ อัปเดต shipping_email จาก email สำเร็จ<br>";
        } else {
            echo "⚠️ ไม่สามารถอัปเดต shipping_email ได้: " . pg_last_error($conn) . "<br>";
        }
    }
    
    // อัปเดต total_amount จาก grand_total
    if (in_array('grand_total', $existingColumns) && in_array('total_amount', $existingColumns)) {
        $updateTotalQuery = "UPDATE orders SET total_amount = grand_total WHERE total_amount IS NULL";
        $updateTotalResult = pg_query($conn, $updateTotalQuery);
        if ($updateTotalResult) {
            echo "✅ อัปเดต total_amount จาก grand_total สำเร็จ<br>";
        } else {
            echo "⚠️ ไม่สามารถอัปเดต total_amount ได้: " . pg_last_error($conn) . "<br>";
        }
    }
    
    // สร้าง order_number สำหรับข้อมูลที่มีอยู่
    if (in_array('order_number', $existingColumns)) {
        $updateOrderNumberQuery = "
            UPDATE orders 
            SET order_number = 'ORD' || to_char(created_at, 'YYYYMMDD') || lpad(id::text, 4, '0')
            WHERE order_number IS NULL
        ";
        $updateOrderNumberResult = pg_query($conn, $updateOrderNumberQuery);
        if ($updateOrderNumberResult) {
            echo "✅ สร้าง order_number สำหรับข้อมูลที่มีอยู่สำเร็จ<br>";
        } else {
            echo "⚠️ ไม่สามารถสร้าง order_number ได้: " . pg_last_error($conn) . "<br>";
        }
    }
    
    // ตรวจสอบโครงสร้างตารางหลังแก้ไข
    echo "<br><h3>📋 โครงสร้างตาราง orders หลังแก้ไข:</h3>";
    $checkColumnsAfterQuery = "
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_name = 'orders'
        ORDER BY ordinal_position
    ";
    
    $checkColumnsAfterResult = pg_query($conn, $checkColumnsAfterQuery);
    if ($checkColumnsAfterResult) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>คอลัมน์</th><th>ประเภทข้อมูล</th><th>Null ได้</th><th>ค่าเริ่มต้น</th></tr>";
        
        while ($row = pg_fetch_assoc($checkColumnsAfterResult)) {
            echo "<tr>";
            echo "<td>{$row['column_name']}</td>";
            echo "<td>{$row['data_type']}</td>";
            echo "<td>{$row['is_nullable']}</td>";
            echo "<td>{$row['column_default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // ตรวจสอบข้อมูลในตาราง
    $checkDataQuery = "SELECT COUNT(*) as total FROM orders";
    $checkDataResult = pg_query($conn, $checkDataQuery);
    
    if ($checkDataResult) {
        $rowCount = pg_fetch_result($checkDataResult, 0, 0);
        echo "<br><h3>📊 ข้อมูลในตาราง:</h3>";
        echo "จำนวนรายการ: $rowCount<br>";
        
        if ($rowCount > 0) {
            // แสดงข้อมูลตัวอย่าง
            $sampleDataQuery = "SELECT id, user_id, order_number, total_amount, shipping_address, shipping_phone, shipping_email, order_status, created_at FROM orders LIMIT 3";
            $sampleDataResult = pg_query($conn, $sampleDataQuery);
            
            if ($sampleDataResult && pg_num_rows($sampleDataResult) > 0) {
                echo "<br><h4>ข้อมูลตัวอย่าง:</h4>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                
                // หัวตาราง
                $firstRow = pg_fetch_assoc($sampleDataResult);
                echo "<tr>";
                foreach ($firstRow as $key => $value) {
                    echo "<th>$key</th>";
                }
                echo "</tr>";
                
                // ข้อมูล
                pg_result_seek($sampleDataResult, 0);
                while ($row = pg_fetch_assoc($sampleDataResult)) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
    pg_close($conn);
    
    echo "<br><hr>";
    echo "<h3>✅ การแก้ไขเสร็จสิ้น!</h3>";
    echo "ตาราง orders พร้อมใช้งานแล้ว<br>";
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
