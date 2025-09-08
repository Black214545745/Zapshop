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

// รับข้อมูล payment method
$paymentMethod = $_POST['payment_method'] ?? '';
$grandTotal = floatval($_POST['grand_total'] ?? 0);

if (empty($paymentMethod) || $grandTotal <= 0) {
    header('Location: payment-methods.php');
    exit();
}

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
    
    // ดึงข้อมูลผู้ใช้
    $userQuery = "SELECT full_name, email, phone, address FROM user_profiles WHERE user_id = $1";
    $userResult = pg_query_params($conn, $userQuery, [$user_id]);
    $userData = pg_fetch_assoc($userResult);
    
    // สร้าง record ใน orders
    $orderQuery = "INSERT INTO orders (id, user_id, fullname, tel, email, address, grand_total, payment_method, order_date, status) 
                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), 'pending')";
    
    $orderParams = [
        $order_id,
        $user_id,
        $userData['full_name'] ?? 'ไม่ระบุชื่อ',
        $userData['phone'] ?? 'ไม่ระบุเบอร์โทร',
        $userData['email'] ?? 'ไม่ระบุอีเมล',
        $userData['address'] ?? 'ไม่ระบุที่อยู่',
        $grandTotal,
        $paymentMethod
    ];
    
    $orderResult = pg_query_params($conn, $orderQuery, $orderParams);
    if (!$orderResult) {
        throw new Exception("Error creating order: " . pg_last_error($conn));
    }
    
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