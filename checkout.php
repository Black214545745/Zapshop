<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit();
}

// ตรวจสอบว่ามีสินค้าในตะกร้าหรือไม่
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// ตรวจสอบว่ามีข้อมูล payment method หรือไม่
if (!isset($_POST['payment_method']) || !isset($_POST['grand_total'])) {
    header('Location: payment-methods.php');
    exit();
}

$paymentMethod = $_POST['payment_method'];
$grandTotal = floatval($_POST['grand_total']);

// รับข้อมูลที่อยู่จัดส่ง
$shippingData = [
    'full_name' => $_POST['shipping_full_name'] ?? '',
    'phone' => $_POST['shipping_phone'] ?? '',
    'address' => $_POST['shipping_address'] ?? '',
    'province' => $_POST['shipping_province'] ?? '',
    'postal_code' => $_POST['shipping_postal_code'] ?? '',
    'delivery_notes' => $_POST['shipping_delivery_notes'] ?? ''
];

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$totalAmount = 0;
$cartItems = [];

// คำนวณยอดรวมและดึงข้อมูลสินค้า
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

// ตรวจสอบสต็อก
foreach ($cartItems as $item) {
    if ($item['stock'] < $item['quantity']) {
        die("สินค้า {$item['name']} มีสต็อกไม่เพียงพอ (มี {$item['stock']} ชิ้น แต่ต้องการ {$item['quantity']} ชิ้น)");
    }
}

// เริ่ม Transaction
pg_query($conn, "BEGIN");

try {
    // สร้าง Order ID
    $order_id = generateUUID();
    
    // ดึงข้อมูลผู้ใช้ - ตรวจสอบในหลายตาราง
    $userData = null;
    
    // ลองดึงจาก user_profiles ก่อน
    $userQuery = "SELECT full_name, email, phone, address FROM user_profiles WHERE user_id = $1";
    $userResult = pg_query_params($conn, $userQuery, [$user_id]);
    if ($userResult && pg_num_rows($userResult) > 0) {
        $userData = pg_fetch_assoc($userResult);
    } else {
        // ถ้าไม่มีใน user_profiles ลองดึงจาก customers
        $customerQuery = "SELECT first_name || ' ' || last_name as full_name, email, phone, '' as address FROM customers WHERE id = $1";
        $customerResult = pg_query_params($conn, $customerQuery, [$user_id]);
        if ($customerResult && pg_num_rows($customerResult) > 0) {
            $userData = pg_fetch_assoc($customerResult);
        } else {
            // ถ้าไม่มีใน customers ลองดึงจาก users
            $usersQuery = "SELECT username as full_name, email, '' as phone, '' as address FROM users WHERE id = $1";
            $usersResult = pg_query_params($conn, $usersQuery, [$user_id]);
            if ($usersResult && pg_num_rows($usersResult) > 0) {
                $userData = pg_fetch_assoc($usersResult);
            }
        }
    }
    
    // ถ้ายังไม่มีข้อมูล ให้ใช้ข้อมูลเริ่มต้น
    if (!$userData) {
        $userData = [
            'full_name' => 'ไม่ระบุชื่อ',
            'email' => 'ไม่ระบุอีเมล',
            'phone' => 'ไม่ระบุเบอร์โทร',
            'address' => 'ไม่ระบุที่อยู่'
        ];
    }
    
    // ใช้ข้อมูลที่อยู่จัดส่งที่ลูกค้ากรอก (ถ้ามี) หรือใช้ข้อมูลจากฐานข้อมูล
    $finalShippingData = [
        'full_name' => !empty($shippingData['full_name']) ? $shippingData['full_name'] : $userData['full_name'],
        'phone' => !empty($shippingData['phone']) ? $shippingData['phone'] : $userData['phone'],
        'email' => $userData['email'],
        'address' => !empty($shippingData['address']) ? $shippingData['address'] : $userData['address']
    ];
    
    // รวมข้อมูลที่อยู่จัดส่งทั้งหมด
    $fullAddress = $finalShippingData['address'];
    if (!empty($shippingData['province'])) {
        $fullAddress .= ', ' . $shippingData['province'];
    }
    if (!empty($shippingData['postal_code'])) {
        $fullAddress .= ' ' . $shippingData['postal_code'];
    }
    if (!empty($shippingData['delivery_notes'])) {
        $fullAddress .= ' (หมายเหตุ: ' . $shippingData['delivery_notes'] . ')';
    }
    
    // สร้าง record ใน orders (ใช้โครงสร้าง UUID ที่ถูกต้อง)
    $orderQuery = "INSERT INTO orders (id, user_id, fullname, tel, email, address, grand_total, payment_method, order_date, status) 
                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), 'pending')";
    
    $orderParams = [
        $order_id,
        $user_id,
        $finalShippingData['full_name'] ?? 'ไม่ระบุชื่อ',
        $finalShippingData['phone'] ?? 'ไม่ระบุเบอร์โทร',
        $finalShippingData['email'] ?? 'ไม่ระบุอีเมล',
        $fullAddress, // ใช้ที่อยู่จัดส่งที่รวมข้อมูลทั้งหมด
        $grandTotal, // ใช้ grandTotal ที่รวมค่าจัดส่งแล้ว
        $paymentMethod
    ];
    
    $orderResult = pg_query_params($conn, $orderQuery, $orderParams);
    if (!$orderResult) {
        throw new Exception("Error creating order: " . pg_last_error($conn));
    }
    
    // บันทึกลง order_details (ใช้โครงสร้าง UUID ที่ถูกต้อง)
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
        
        // อัปเดตสต็อกสินค้า
        $newStock = $item['stock'] - $item['quantity'];
        $updateStockQuery = "UPDATE products SET current_stock = $1 WHERE id = $2";
        $updateStockResult = pg_query_params($conn, $updateStockQuery, [$newStock, $item['id']]);
        if (!$updateStockResult) {
            throw new Exception("Error updating stock: " . pg_last_error($conn));
        }
    }
    
    // Commit Transaction
    pg_query($conn, "COMMIT");
    
    // ล้างตะกร้าสินค้า
    unset($_SESSION['cart']);
    
    // Redirect ไปหน้า payment process
    header("Location: payment-process-display.php?order_id=" . $order_id . "&method=" . $paymentMethod);
    exit();
    
} catch (Exception $e) {
    // Rollback Transaction
    pg_query($conn, "ROLLBACK");
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
} finally {
    pg_close($conn);
}
?>
