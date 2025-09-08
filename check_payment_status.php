<?php
require_once 'config.php';

// ตั้งค่า header สำหรับ JSON response
header('Content-Type: application/json');

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// รับข้อมูล
$order_id = intval($_POST['order_id'] ?? 0);

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

try {
    $conn = getConnection();
    
    // ดึงข้อมูลสถานะการชำระเงิน
    $query = "SELECT o.id, o.order_number, o.total_amount, o.order_status, o.created_at,
                     p.payment_status, p.amount, p.payment_date, p.payment_method
              FROM orders o 
              LEFT JOIN payments p ON o.id = p.order_id 
              WHERE o.id = $1";
    
    $result = pg_query_params($conn, $query, [$order_id]);
    
    if (!$result || pg_num_rows($result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $orderData = pg_fetch_assoc($result);
    
    // ส่งข้อมูลกลับ
    echo json_encode([
        'success' => true,
        'order_id' => $orderData['id'],
        'order_number' => $orderData['order_number'],
        'total_amount' => floatval($orderData['total_amount']),
        'order_status' => $orderData['order_status'],
        'payment_status' => $orderData['payment_status'],
        'payment_method' => $orderData['payment_method'],
        'amount_paid' => $orderData['amount'] ? floatval($orderData['amount']) : 0,
        'payment_date' => $orderData['payment_date'],
        'order_date' => $orderData['created_at'],
        'is_paid' => $orderData['payment_status'] === 'paid'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        pg_close($conn);
    }
}
?>
