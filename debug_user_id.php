<?php
session_start();
include 'config.php';

echo "<h2>🔍 Debug User ID</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>ไม่พบ user_id ใน session</p>";
    exit();
}

$user_id = $_SESSION['user_id'];

echo "<h3>ข้อมูล User ID:</h3>";
echo "<pre>";
echo "User ID: " . $user_id . "\n";
echo "Type: " . gettype($user_id) . "\n";
echo "Length: " . strlen($user_id) . "\n";
echo "Is UUID: " . (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $user_id) ? 'Yes' : 'No') . "\n";
echo "</pre>";

// ทดสอบการเชื่อมต่อฐานข้อมูล
echo "<h3>ทดสอบการเชื่อมต่อฐานข้อมูล:</h3>";
try {
    $conn = getConnection();
    echo "<p style='color: green;'>✅ เชื่อมต่อฐานข้อมูลสำเร็จ</p>";
    
    // ทดสอบการ query user_profiles
    $userQuery = "SELECT full_name, email, phone, address FROM user_profiles WHERE user_id = $1";
    echo "<p>Query: " . $userQuery . "</p>";
    echo "<p>Parameter: " . $user_id . "</p>";
    
    $userResult = pg_query_params($conn, $userQuery, [$user_id]);
    
    if ($userResult) {
        echo "<p style='color: green;'>✅ Query user_profiles สำเร็จ</p>";
        $userData = pg_fetch_assoc($userResult);
        if ($userData) {
            echo "<pre>";
            print_r($userData);
            echo "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠️ ไม่พบข้อมูลผู้ใช้</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Query user_profiles ล้มเหลว: " . pg_last_error($conn) . "</p>";
    }
    
    // ทดสอบการสร้าง Order
    echo "<h3>ทดสอบการสร้าง Order:</h3>";
    $order_id = generateUUID();
    echo "<p>Order ID: " . $order_id . "</p>";
    
    $orderQuery = "INSERT INTO orders (id, user_id, fullname, tel, email, address, grand_total, payment_method, order_date, status) 
                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), 'pending')";
    
    $orderParams = [
        $order_id,
        $user_id,
        'ทดสอบ',
        '0812345678',
        'test@example.com',
        'ที่อยู่ทดสอบ',
        1000.00,
        'promptpay'
    ];
    
    echo "<p>Query: " . $orderQuery . "</p>";
    echo "<p>Parameters:</p>";
    echo "<pre>";
    print_r($orderParams);
    echo "</pre>";
    
    $orderResult = pg_query_params($conn, $orderQuery, $orderParams);
    
    if ($orderResult) {
        echo "<p style='color: green;'>✅ สร้าง Order สำเร็จ</p>";
    } else {
        echo "<p style='color: red;'>❌ สร้าง Order ล้มเหลว: " . pg_last_error($conn) . "</p>";
    }
    
    pg_close($conn);
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
