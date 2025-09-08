<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// รับข้อมูล
$order_id = $_POST['order_id'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($order_id) || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit();
}

try {
    $conn = getConnection();
    
    // เริ่ม Transaction
    pg_query($conn, "BEGIN");
    
    // อัปเดตสถานะ order
    $updateOrderQuery = "UPDATE orders SET status = $1 WHERE id = $2";
    $updateOrderResult = pg_query_params($conn, $updateOrderQuery, [$status === 'paid' ? 'completed' : 'pending', $order_id]);
    
    if (!$updateOrderResult) {
        throw new Exception('ไม่สามารถอัปเดตสถานะ order ได้: ' . pg_last_error($conn));
    }
    
    // สร้างหรืออัปเดต payment record
    $checkPaymentQuery = "SELECT id FROM payments WHERE order_id = $1";
    $checkPaymentResult = pg_query_params($conn, $checkPaymentQuery, [$order_id]);
    
    if ($checkPaymentResult && pg_num_rows($checkPaymentResult) > 0) {
        // อัปเดต payment ที่มีอยู่
        $updatePaymentQuery = "UPDATE payments SET payment_status = $1, payment_date = NOW() WHERE order_id = $2";
        $updatePaymentResult = pg_query_params($conn, $updatePaymentQuery, [$status, $order_id]);
        
        if (!$updatePaymentResult) {
            throw new Exception('ไม่สามารถอัปเดตสถานะ payment ได้: ' . pg_last_error($conn));
        }
    } else {
        // สร้าง payment record ใหม่
        $insertPaymentQuery = "INSERT INTO payments (order_id, payment_method, payment_status, payment_date, amount) 
                              SELECT id, payment_method, $1, NOW(), grand_total FROM orders WHERE id = $2";
        $insertPaymentResult = pg_query_params($conn, $insertPaymentQuery, [$status, $order_id]);
        
        if (!$insertPaymentResult) {
            throw new Exception('ไม่สามารถสร้าง payment record ได้: ' . pg_last_error($conn));
        }
    }
    
    // Commit Transaction
    pg_query($conn, "COMMIT");
    
    pg_close($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'อัปเดตสถานะการชำระเงินเรียบร้อยแล้ว'
    ]);
    
} catch (Exception $e) {
    // Rollback Transaction
    if (isset($conn)) {
        pg_query($conn, "ROLLBACK");
        pg_close($conn);
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>