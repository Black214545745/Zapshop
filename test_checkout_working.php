<?php
session_start();
include 'config.php';

echo "<h2>🧪 ทดสอบการ Checkout (Working Version)</h2>";

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>กรุณาเข้าสู่ระบบก่อน</p>";
    exit();
}

// สร้างข้อมูลทดสอบในตะกร้า
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        '4ae1eeed-a3ee-4434-801e-284514745a7b' => 2
    ];
}

echo "<h3>ข้อมูล Session:</h3>";
echo "<pre>";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User ID Type: " . gettype($_SESSION['user_id']) . "\n";
echo "Cart: " . json_encode($_SESSION['cart']) . "\n";
echo "</pre>";

// ทดสอบการสร้าง Order
echo "<h3>ทดสอบการสร้าง Order:</h3>";

try {
    $conn = getConnection();
    
    // ตรวจสอบโครงสร้างตาราง orders
    echo "<h4>โครงสร้างตาราง orders:</h4>";
    $result = pg_query($conn, "SELECT column_name, data_type 
                               FROM information_schema.columns 
                               WHERE table_name = 'orders' 
                               ORDER BY ordinal_position");
    
    if ($result) {
        echo "<ul>";
        while ($row = pg_fetch_assoc($result)) {
            echo "<li><strong>{$row['column_name']}</strong> - {$row['data_type']}</li>";
        }
        echo "</ul>";
    }
    
    // เริ่ม Transaction
    pg_query($conn, "BEGIN");
    
    // สร้าง Order ID
    $order_id = generateUUID();
    echo "<p>Order ID: " . $order_id . "</p>";
    
    // ดึงข้อมูลผู้ใช้
    $userQuery = "SELECT full_name, email, phone, address FROM user_profiles WHERE user_id = $1";
    $userResult = pg_query_params($conn, $userQuery, [$_SESSION['user_id']]);
    $userData = pg_fetch_assoc($userResult);
    
    if (!$userData) {
        echo "<p style='color: red;'>ไม่พบข้อมูลผู้ใช้</p>";
        pg_query($conn, "ROLLBACK");
        exit();
    }
    
    echo "<p>User Data: " . json_encode($userData) . "</p>";
    
    // คำนวณยอดรวม
    $totalAmount = 0;
    $cartItems = [];
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $query = "SELECT id, name, price, current_stock FROM products WHERE id = $1";
        $result = pg_query_params($conn, $query, [$product_id]);
        
        if ($result && pg_num_rows($result) > 0) {
            $product = pg_fetch_assoc($result);
            $itemTotal = $product['price'] * $quantity;
            $totalAmount += $itemTotal;
            
            $cartItems[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'total' => $itemTotal,
                'stock' => $product['current_stock']
            ];
        }
    }
    
    echo "<p>Total Amount: " . $totalAmount . "</p>";
    echo "<p>Cart Items: " . count($cartItems) . " รายการ</p>";
    
    // สร้าง record ใน orders (ใช้โครงสร้างที่ถูกต้อง)
    $orderQuery = "INSERT INTO orders (id, user_id, fullname, tel, email, address, grand_total, payment_method, order_date, status) 
                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), 'pending')";
    
    $orderParams = [
        $order_id,
        $_SESSION['user_id'],
        $userData['full_name'] ?? 'ไม่ระบุชื่อ',
        $userData['phone'] ?? 'ไม่ระบุเบอร์โทร',
        $userData['email'] ?? 'ไม่ระบุอีเมล',
        $userData['address'] ?? 'ไม่ระบุที่อยู่',
        $totalAmount,
        'promptpay'
    ];
    
    echo "<p>กำลังสร้าง Order...</p>";
    $orderResult = pg_query_params($conn, $orderQuery, $orderParams);
    if (!$orderResult) {
        throw new Exception("Error creating order: " . pg_last_error($conn));
    }
    
    echo "<p style='color: green;'>✅ สร้าง Order สำเร็จ!</p>";
    
    // บันทึกลง order_details
    foreach ($cartItems as $item) {
        $itemQuery = "INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total) 
                      VALUES ($1, $2, $3, $4, $5, $6)";
        
        $itemParams = [
            $order_id,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity'],
            $item['total']
        ];
        
        $itemResult = pg_query_params($conn, $itemQuery, $itemParams);
        if (!$itemResult) {
            throw new Exception("Error creating order detail: " . pg_last_error($conn));
        }
    }
    
    echo "<p style='color: green;'>✅ สร้าง Order Details สำเร็จ!</p>";
    
    // Commit Transaction
    pg_query($conn, "COMMIT");
    
    echo "<h3>🎉 Checkout สำเร็จ!</h3>";
    echo "<p>Order ID: " . $order_id . "</p>";
    echo "<p>Total Amount: ฿" . number_format($totalAmount, 2) . "</p>";
    
    // ล้างตะกร้าสินค้า
    unset($_SESSION['cart']);
    
} catch (Exception $e) {
    // Rollback Transaction
    pg_query($conn, "ROLLBACK");
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
} finally {
    pg_close($conn);
}
?>