<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

// รับข้อมูลจาก POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit();
}

try {
    $conn = getConnection();
    
    // เริ่ม Transaction
    pg_query($conn, "BEGIN");
    
    // สร้าง Order ID
    $order_id = generateUUID();
    
    // สร้าง record ใน orders
    $orderQuery = "INSERT INTO orders (id, user_id, fullname, tel, email, address, grand_total, payment_method, order_date, status) 
                   VALUES ($1, $2, $3, $4, $5, $6, $7, $8, NOW(), 'pending')";
    
    $orderParams = [
        $order_id,
        $input['user_id'],
        $input['fullname'],
        $input['tel'],
        $input['email'],
        $input['address'],
        $input['grand_total'],
        $input['payment_method']
    ];
    
    $orderResult = pg_query_params($conn, $orderQuery, $orderParams);
    if (!$orderResult) {
        throw new Exception("Error creating order: " . pg_last_error($conn));
    }
    
    // สร้าง order_details ตัวอย่าง
    $itemQuery = "INSERT INTO order_details (order_id, product_id, product_name, price, quantity, total) 
                  VALUES ($1, $2, $3, $4, $5, $6)";
    
    $itemParams = [
        $order_id,
        generateUUID(), // product_id ตัวอย่าง
        'สินค้าทดสอบ',
        100.00,
        1,
        100.00
    ];
    
    $itemResult = pg_query_params($conn, $itemQuery, $itemParams);
    if (!$itemResult) {
        throw new Exception("Error creating order detail: " . pg_last_error($conn));
    }
    
    // Commit Transaction
    pg_query($conn, "COMMIT");
    
    echo json_encode([
        'success' => true, 
        'message' => 'สร้าง Order สำเร็จ',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback Transaction
    pg_query($conn, "ROLLBACK");
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
} finally {
    pg_close($conn);
}
?>
