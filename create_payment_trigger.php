<?php
require_once 'config.php';

echo "<h2>🔧 สร้าง Payment Trigger สำหรับ Auto-Update Order Status</h2>";

try {
    $conn = getConnection();
    
    // 1. สร้าง Function สำหรับ Trigger
    echo "<h3>📝 กำลังสร้าง Function...</h3>";
    
    $createFunctionQuery = "
    CREATE OR REPLACE FUNCTION update_order_status()
    RETURNS TRIGGER AS $$
    BEGIN
        -- เช็คว่า order_id ที่เกี่ยวข้องยังมี payment ที่จ่ายแล้วอยู่หรือไม่
        IF EXISTS (
            SELECT 1
            FROM payments
            WHERE order_id = NEW.order_id
              AND payment_status = 'paid'
        ) THEN
            -- มีอย่างน้อย 1 payment เป็น paid → อัปเดต order เป็น paid
            UPDATE orders
            SET order_status = 'paid',
                updated_at = NOW()
            WHERE id = NEW.order_id;
            
            -- Log การทำงาน (ถ้ามีตาราง activity_logs)
            BEGIN
                INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
                VALUES (
                    NEW.user_id,
                    'payment_completed',
                    'Payment completed via smart trigger for order ' || NEW.order_id,
                    'payments',
                    NEW.id
                );
            EXCEPTION
                WHEN OTHERS THEN
                    -- ไม่ critical ถ้า log ไม่สำเร็จ
                    NULL;
            END;
        ELSE
            -- ไม่มี payment ที่เป็น paid → กลับไป pending
            UPDATE orders
            SET order_status = 'pending',
                updated_at = NOW()
            WHERE id = NEW.order_id;
        END IF;

        RETURN NEW;
    END;
    $$ LANGUAGE plpgsql;
    ";
    
    $functionResult = pg_query($conn, $createFunctionQuery);
    if (!$functionResult) {
        throw new Exception("ไม่สามารถสร้าง Function ได้: " . pg_last_error($conn));
    }
    echo "✅ สร้าง Function สำเร็จ<br>";
    
    // 2. สร้าง Trigger บนตาราง payments
    echo "<h3>🔗 กำลังสร้าง Trigger...</h3>";
    
    // ลบ Trigger และ Function เดิม (ถ้ามี)
    $dropTriggerQuery = "DROP TRIGGER IF EXISTS trg_update_order_status ON payments";
    $dropResult = pg_query($conn, $dropTriggerQuery);
    if ($dropResult) {
        echo "✅ ลบ Trigger เดิมสำเร็จ<br>";
    }
    
    $dropFunctionQuery = "DROP FUNCTION IF EXISTS update_order_status()";
    $dropFunctionResult = pg_query($conn, $dropFunctionQuery);
    if ($dropFunctionResult) {
        echo "✅ ลบ Function เดิมสำเร็จ<br>";
    }
    
    // สร้าง Trigger อัจฉริยะใหม่
    $createTriggerQuery = "
    CREATE TRIGGER trg_update_order_status
    AFTER INSERT OR UPDATE ON payments
    FOR EACH ROW
    EXECUTE FUNCTION update_order_status();
    ";
    
    $triggerResult = pg_query($conn, $createTriggerQuery);
    if (!$triggerResult) {
        throw new Exception("ไม่สามารถสร้าง Trigger ได้: " . pg_last_error($conn));
    }
    echo "✅ สร้าง Trigger สำเร็จ<br>";
    
    // 3. สร้าง Trigger เพิ่มเติมสำหรับ DELETE (กรณีลบ payment)
    echo "<h3>🔗 กำลังสร้าง Delete Trigger...</h3>";
    
    $createDeleteTriggerQuery = "
    CREATE TRIGGER trg_update_order_status_delete
    AFTER DELETE ON payments
    FOR EACH ROW
    EXECUTE FUNCTION update_order_status();
    ";
    
    $deleteTriggerResult = pg_query($conn, $createDeleteTriggerQuery);
    if (!$deleteTriggerResult) {
        throw new Exception("ไม่สามารถสร้าง Delete Trigger ได้: " . pg_last_error($conn));
    }
    echo "✅ สร้าง Delete Trigger สำเร็จ<br>";
    
    // 4. ตรวจสอบ Trigger ที่สร้าง
    echo "<h3>📋 ตรวจสอบ Trigger ที่สร้าง...</h3>";
    
    $checkTriggerQuery = "
    SELECT 
        trigger_name,
        event_manipulation,
        action_timing,
        action_statement
    FROM information_schema.triggers 
    WHERE event_object_table = 'payments'
    ORDER BY trigger_name;
    ";
    
    $checkResult = pg_query($conn, $checkTriggerQuery);
    if ($checkResult) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr><th>Trigger Name</th><th>Event</th><th>Timing</th><th>Action</th></tr>";
        
        while ($row = pg_fetch_assoc($checkResult)) {
            echo "<tr>";
            echo "<td>{$row['trigger_name']}</td>";
            echo "<td>{$row['event_manipulation']}</td>";
            echo "<td>{$row['action_timing']}</td>";
            echo "<td>{$row['action_statement']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 5. ทดสอบ Trigger
    echo "<h3>🧪 ทดสอบ Trigger...</h3>";
    
    // หา order ที่มี payment_status = 'pending'
    $testQuery = "
    SELECT p.id, p.order_id, p.payment_status, o.order_status
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.payment_status = 'pending'
    LIMIT 1;
    ";
    
    $testResult = pg_query($conn, $testQuery);
    if ($testResult && pg_num_rows($testResult) > 0) {
        $testData = pg_fetch_assoc($testResult);
        $paymentId = $testData['id'];
        $orderId = $testData['order_id'];
        
        echo "🔍 พบ Payment ID: {$paymentId}, Order ID: {$orderId}<br>";
        echo "📊 สถานะก่อนทดสอบ: Payment = {$testData['payment_status']}, Order = {$testData['order_status']}<br>";
        
        // ทดสอบ UPDATE payment_status เป็น 'paid'
        $updateQuery = "UPDATE payments SET payment_status = 'paid' WHERE id = $1";
        $updateResult = pg_query_params($conn, $updateQuery, [$paymentId]);
        
        if ($updateResult) {
            // ตรวจสอบผลลัพธ์
            $checkUpdateQuery = "
            SELECT p.payment_status, o.order_status
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            WHERE p.id = $1;
            ";
            
            $checkUpdateResult = pg_query_params($conn, $checkUpdateQuery, [$paymentId]);
            if ($checkUpdateResult && pg_num_rows($checkUpdateResult) > 0) {
                $updateData = pg_fetch_assoc($checkUpdateResult);
                echo "✅ หลังทดสอบ: Payment = {$updateData['payment_status']}, Order = {$updateData['order_status']}<br>";
                
                if ($updateData['payment_status'] === 'paid' && $updateData['order_status'] === 'paid') {
                    echo "🎉 Trigger ทำงานสำเร็จ! Order status อัปเดตอัตโนมัติ<br>";
                } else {
                    echo "⚠️ Trigger อาจมีปัญหา<br>";
                }
            }
            
            // คืนค่าสถานะเดิมเพื่อไม่ให้กระทบข้อมูลจริง
            $revertQuery = "UPDATE payments SET payment_status = 'pending' WHERE id = $1";
            pg_query_params($conn, $revertQuery, [$paymentId]);
            echo "🔄 คืนค่าสถานะเดิมแล้ว<br>";
            
        } else {
            echo "❌ ไม่สามารถทดสอบได้: " . pg_last_error($conn) . "<br>";
        }
    } else {
        echo "ℹ️ ไม่มี payment ที่ pending ให้ทดสอบ<br>";
    }
    
    // 6. แสดงข้อมูลการใช้งาน
    echo "<h3>📖 วิธีการใช้งาน</h3>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h4>✅ การทำงานของ Trigger อัจฉริยะ (เวอร์ชันไป-กลับ):</h4>";
    echo "<ul>";
    echo "<li><strong>มี Payment ใดเป็น 'paid'</strong> → order_status = 'paid'</li>";
    echo "<li><strong>ไม่มี Payment ที่เป็น 'paid' เลย</strong> → order_status = 'pending'</li>";
    echo "<li><strong>รองรับการ Rollback</strong> → เปลี่ยนกลับเป็น pending ได้อัตโนมัติ</li>";
    echo "<li><strong>รองรับหลาย Payment ต่อ Order</strong> → เก็บสถานะแยกได้ครบ</li>";
    echo "</ul>";
    
    echo "<h4>🔧 ตัวอย่างการใช้งาน Trigger อัจฉริยะ (เวอร์ชันไป-กลับ):</h4>";
    echo "<pre><code>-- เพิ่ม Payment ใหม่
INSERT INTO payments (order_id, amount, payment_method, payment_status)
VALUES (123, 500.00, 'QR', 'pending');

-- อัปเดต Payment เป็น paid
UPDATE payments 
SET payment_status = 'paid' 
WHERE id = 1;

-- เปลี่ยนกลับเป็น failed (Rollback)
UPDATE payments 
SET payment_status = 'failed' 
WHERE id = 1;

-- Trigger จะรันอัตโนมัติและตรวจสอบ:
-- ถ้ามี Payment ใดเป็น paid → Order = 'paid'
-- ถ้าไม่มี Payment ที่เป็น paid เลย → Order = 'pending' (Rollback)
-- ไม่ต้องเรียก PHP หรือ webhook เพิ่ม</code></pre>";
    
    echo "<h4>⚠️ ข้อควรระวัง:</h4>";
    echo "<ul>";
    echo "<li>Trigger จะรันทุกครั้งที่ INSERT/UPDATE/DELETE payments</li>";
    echo "<li>ควรทดสอบกับ DB Test ก่อน deploy จริง</li>";
    echo "<li>รองรับหลาย Payment ต่อ Order ได้แล้ว</li>";
    echo "<li>Logic อัจฉริยะ: ตรวจสอบ COUNT ของ paid payments</li>";
    echo "</ul>";
    echo "</div>";
    
    pg_close($conn);
    
    echo "<br><hr>";
    echo "<h3>🎉 การสร้าง Trigger อัจฉริยะ (เวอร์ชันไป-กลับ) เสร็จสิ้น!</h3>";
    echo "<p>ระบบจะอัปเดต order_status อัตโนมัติตาม Logic อัจฉริยะ:</p>";
    echo "<ul>";
    echo "<li>มี Payment ใดเป็น 'paid' → Order = 'paid'</li>";
    echo "<li>ไม่มี Payment ที่เป็น 'paid' เลย → Order = 'pending'</li>";
    echo "<li>รองรับการ Rollback อัตโนมัติ</li>";
    echo "<li>รองรับหลาย Payment ต่อ Order ได้ครบถ้วน</li>";
    echo "</ul>";
    echo "<p>ไม่ต้องเรียก webhook.php อีกต่อไป</p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<br><div style='text-align: center;'>";
echo "<a href='test_trigger.php' class='btn btn-primary' style='margin: 5px; padding: 10px 20px; text-decoration: none; background: #007bff; color: white; border-radius: 5px;'>🧪 ทดสอบ Trigger</a>";
echo "<a href='test_webhook.php' class='btn btn-info' style='margin: 5px; padding: 10px 20px; text-decoration: none; background: #17a2b8; color: white; border-radius: 5px;'>🔗 ทดสอบ Webhook</a>";
echo "<a href='index.php' class='btn btn-secondary' style='margin: 5px; padding: 10px 20px; text-decoration: none; background: #6c757d; color: white; border-radius: 5px;'>🏠 หน้าหลัก</a>";
echo "</div>";
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f8f9fa;
}
h2, h3 { 
    color: #2c3e50; 
    border-bottom: 2px solid #3498db; 
    padding-bottom: 10px;
}
.btn { 
    display: inline-block; 
    padding: 10px 20px; 
    margin: 5px; 
    text-decoration: none; 
    border-radius: 5px; 
    transition: all 0.3s ease;
}
.btn:hover { 
    transform: translateY(-2px); 
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
table { 
    margin: 10px 0; 
    background: white;
}
th, td { 
    padding: 8px; 
    text-align: left; 
    border: 1px solid #ddd;
}
th { 
    background: #e9ecef; 
    font-weight: bold;
}
pre { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 5px; 
    border-left: 4px solid #007bff;
    overflow-x: auto;
}
code { 
    background: #e9ecef; 
    padding: 2px 5px; 
    border-radius: 3px;
}
</style>
