<?php
session_start();
include 'config.php';

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

echo "<h2>แก้ไข Foreign Key Constraint ในตาราง notifications</h2>";

// ตรวจสอบ foreign key constraint ปัจจุบัน
$checkConstraintSQL = "
SELECT 
    tc.constraint_name, 
    tc.table_name, 
    kcu.column_name, 
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name 
FROM 
    information_schema.table_constraints AS tc 
    JOIN information_schema.key_column_usage AS kcu
      ON tc.constraint_name = kcu.constraint_name
      AND tc.table_schema = kcu.table_schema
    JOIN information_schema.constraint_column_usage AS ccu
      ON ccu.constraint_name = tc.constraint_name
      AND ccu.table_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY' 
    AND tc.table_name='notifications'
    AND tc.table_schema='public';
";

$result = pg_query($conn, $checkConstraintSQL);
if ($result) {
    echo "<h3>Foreign Key Constraints ปัจจุบัน:</h3>";
    while ($row = pg_fetch_assoc($result)) {
        echo "<p>Constraint: " . $row['constraint_name'] . " - " . $row['column_name'] . " -> " . $row['foreign_table_name'] . "." . $row['foreign_column_name'] . "</p>";
    }
}

// ลบ foreign key constraint เดิม
$dropConstraintSQL = "ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_user_id_fkey;";
$result = pg_query($conn, $dropConstraintSQL);
if ($result) {
    echo "<p style='color: green;'>✓ ลบ foreign key constraint เดิมสำเร็จ</p>";
} else {
    echo "<p style='color: orange;'>⚠ ไม่พบ foreign key constraint เดิม หรือลบแล้ว</p>";
}

// สร้าง trigger function สำหรับ notifications
$createTriggerFunctionSQL = "
CREATE OR REPLACE FUNCTION validate_notifications_user_id()
RETURNS TRIGGER AS $$
BEGIN
    -- ตรวจสอบว่า user_id มีอยู่ในตาราง users หรือ customers
    IF NEW.user_id IS NOT NULL THEN
        IF NOT EXISTS (
            SELECT 1 FROM users WHERE id = NEW.user_id
        ) AND NOT EXISTS (
            SELECT 1 FROM customers WHERE id = NEW.user_id
        ) THEN
            RAISE EXCEPTION 'user_id % does not exist in users or customers table', NEW.user_id;
        END IF;
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
";

$result = pg_query($conn, $createTriggerFunctionSQL);
if ($result) {
    echo "<p style='color: green;'>✓ สร้าง trigger function สำหรับ notifications สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้าง trigger function ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// สร้าง trigger สำหรับ notifications
$createTriggerSQL = "
CREATE OR REPLACE TRIGGER tr_validate_notifications_user_id
    BEFORE INSERT OR UPDATE ON notifications
    FOR EACH ROW
    EXECUTE FUNCTION validate_notifications_user_id();
";

$result = pg_query($conn, $createTriggerSQL);
if ($result) {
    echo "<p style='color: green;'>✓ สร้าง trigger สำหรับ notifications สำเร็จ</p>";
} else {
    echo "<p style='color: red;'>✗ สร้าง trigger ล้มเหลว: " . pg_last_error($conn) . "</p>";
}

// ทดสอบการทำงาน
echo "<h3>ทดสอบการทำงาน:</h3>";

// ทดสอบด้วย customer_id
$testCustomerSQL = "SELECT id FROM customers LIMIT 1";
$testResult = pg_query($conn, $testCustomerSQL);
if ($testResult && pg_num_rows($testResult) > 0) {
    $customer = pg_fetch_assoc($testResult);
    $testInsertSQL = "INSERT INTO notifications (user_id, title, message, type) VALUES ($1, 'Test Notification', 'This is a test notification', 'info')";
    $testInsertResult = pg_query_params($conn, $testInsertSQL, [$customer['id']]);
    
    if ($testInsertResult) {
        echo "<p style='color: green;'>✓ ทดสอบการบันทึก notification ด้วย customer_id สำเร็จ</p>";
        
        // ลบข้อมูลทดสอบ
        $deleteTestSQL = "DELETE FROM notifications WHERE title = 'Test Notification'";
        pg_query($conn, $deleteTestSQL);
    } else {
        echo "<p style='color: red;'>✗ ทดสอบการบันทึก notification ล้มเหลว: " . pg_last_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ ไม่พบข้อมูลลูกค้าสำหรับทดสอบ</p>";
}

// ตรวจสอบตารางอื่นๆ ที่อาจมีปัญหาเดียวกัน
echo "<h3>ตรวจสอบตารางอื่นๆ:</h3>";

$checkOtherTablesSQL = "
SELECT 
    tc.table_name,
    tc.constraint_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name
FROM 
    information_schema.table_constraints AS tc 
    JOIN information_schema.key_column_usage AS kcu
      ON tc.constraint_name = kcu.constraint_name
      AND tc.table_schema = kcu.table_schema
    JOIN information_schema.constraint_column_usage AS ccu
      ON ccu.constraint_name = tc.constraint_name
      AND ccu.table_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY' 
    AND tc.table_schema='public'
    AND ccu.table_name = 'users'
    AND kcu.column_name LIKE '%user_id%'
ORDER BY tc.table_name;
";

$result = pg_query($conn, $checkOtherTablesSQL);
if ($result) {
    echo "<p><strong>ตารางอื่นๆ ที่มี foreign key ไปยัง users:</strong></p>";
    while ($row = pg_fetch_assoc($result)) {
        echo "<p>- " . $row['table_name'] . "." . $row['column_name'] . " -> " . $row['foreign_table_name'] . " (Constraint: " . $row['constraint_name'] . ")</p>";
    }
}

pg_close($conn);

echo "<h3>✅ แก้ไข Foreign Key Constraint สำหรับ notifications เสร็จสิ้น!</h3>";
echo "<p><strong>สิ่งที่ทำ:</strong></p>";
echo "<ul>";
echo "<li>ลบ foreign key constraint เดิมที่อ้างอิงไปยังตาราง users เท่านั้น</li>";
echo "<li>สร้าง trigger function ที่ตรวจสอบว่า user_id มีอยู่ในตาราง users หรือ customers</li>";
echo "<li>สร้าง trigger ที่ทำงานก่อนการ INSERT หรือ UPDATE ในตาราง notifications</li>";
echo "<li>ทดสอบการทำงานด้วยข้อมูลลูกค้า</li>";
echo "<li>ตรวจสอบตารางอื่นๆ ที่อาจมีปัญหาเดียวกัน</li>";
echo "</ul>";

echo "<p><strong>ผลลัพธ์:</strong> ตอนนี้ตาราง notifications สามารถบันทึกข้อมูลจากทั้งตาราง users (พนักงาน) และ customers (ลูกค้า) ได้แล้ว</p>";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไข Foreign Key Constraint - Notifications</title>
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
        <h1 style="text-align: center; color: #007bff;">🔧 แก้ไข Foreign Key Constraint - Notifications</h1>
        
        <div style="margin-top: 30px;">
            <h3>🔗 ลิงก์ที่เกี่ยวข้อง</h3>
            <ul>
                <li><a href="user-login.php">🔐 เข้าสู่ระบบลูกค้า</a></li>
                <li><a href="user-register.php">📝 สมัครสมาชิกลูกค้า</a></li>
                <li><a href="admin-dashboard.php">📊 Admin Dashboard</a></li>
                <li><a href="index.php">🏠 หน้าแรก</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
