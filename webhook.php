<?php
require_once 'config.php';

// ตั้งค่า header สำหรับ JSON response
header('Content-Type: application/json');

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

// รับข้อมูลจาก webhook
$payload = file_get_contents("php://input");
$data = json_decode($payload, true);

// Log webhook data สำหรับ debug
error_log("Webhook received: " . $payload);

// ตรวจสอบข้อมูลที่จำเป็น
if (!$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit();
}

// ข้อมูลที่ธนาคารส่งมา (ปรับตาม API ของธนาคารจริง)
$order_id = intval($data['order_id'] ?? $data['ref1'] ?? 0);
$amountPaid = floatval($data['amount'] ?? $data['total_amount'] ?? 0);
$transaction_id = $data['transaction_id'] ?? $data['ref2'] ?? '';
$payment_date = $data['payment_date'] ?? date('Y-m-d H:i:s');
$status = $data['status'] ?? $data['payment_status'] ?? '';

// ตรวจสอบข้อมูล
if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
    exit();
}

if ($amountPaid <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
    exit();
}

try {
    $conn = getConnection();
    
    // เริ่ม Transaction
    pg_query($conn, "BEGIN");
    
    try {
        // ตรวจสอบว่ามี order นี้อยู่จริงหรือไม่
        $checkQuery = "SELECT o.id, o.order_number, o.total_amount, o.order_status, 
                              p.id as payment_id, p.payment_status, p.amount
                       FROM orders o 
                       LEFT JOIN payments p ON o.id = p.order_id 
                       WHERE o.id = $1";
        
        $checkResult = pg_query_params($conn, $checkQuery, [$order_id]);
        
        if (!$checkResult || pg_num_rows($checkResult) == 0) {
            throw new Exception('Order not found');
        }
        
        $orderData = pg_fetch_assoc($checkResult);
        
        // ตรวจสอบว่ายังไม่ได้ชำระเงิน
        if ($orderData['payment_status'] === 'paid') {
            throw new Exception('Order already paid');
        }
        
        // ตรวจสอบยอดเงิน
        if (abs($amountPaid - $orderData['total_amount']) > 0.01) {
            throw new Exception('Amount mismatch');
        }
        
        // อัปเดต payment
        $updatePaymentQuery = "UPDATE payments 
                              SET payment_status = 'paid', 
                                  amount = $1, 
                                  payment_date = $2, 
                                  payment_details = $3,
                                  updated_at = CURRENT_TIMESTAMP 
                              WHERE order_id = $4";
        
        $paymentDetails = json_encode([
            'transaction_id' => $transaction_id,
            'webhook_data' => $data,
            'received_at' => date('Y-m-d H:i:s')
        ]);
        
        $updatePaymentResult = pg_query_params($conn, $updatePaymentQuery, [
            $amountPaid,
            $payment_date,
            $paymentDetails,
            $order_id
        ]);
        
        if (!$updatePaymentResult) {
            throw new Exception('Failed to update payment: ' . pg_last_error($conn));
        }
        
        // อัปเดต order status
        $updateOrderQuery = "UPDATE orders 
                            SET order_status = 'paid', 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE id = $1";
        
        $updateOrderResult = pg_query_params($conn, $updateOrderQuery, [$order_id]);
        
        if (!$updateOrderResult) {
            throw new Exception('Failed to update order: ' . pg_last_error($conn));
        }
        
        // บันทึก activity log (ถ้ามีตาราง)
        try {
            $logQuery = "INSERT INTO activity_logs (user_id, action, description, table_name, record_id) 
                        VALUES ($1, 'payment_received', $2, 'payments', $3)";
            
            $logDescription = "Payment received for order {$orderData['order_number']} via webhook";
            
            pg_query_params($conn, $logQuery, [
                $orderData['user_id'] ?? null,
                $logDescription,
                $order_id
            ]);
        } catch (Exception $logError) {
            // ไม่ critical ถ้า log ไม่สำเร็จ
            error_log("Failed to log activity: " . $logError->getMessage());
        }
        
        // Commit Transaction
        pg_query($conn, "COMMIT");
        
        // ส่ง response สำเร็จ
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Payment processed successfully',
            'order_id' => $order_id,
            'order_number' => $orderData['order_number'],
            'amount_paid' => $amountPaid,
            'processed_at' => date('Y-m-d H:i:s')
        ]);
        
        // Log สำเร็จ
        error_log("Payment processed successfully for order {$orderData['order_number']}");
        
    } catch (Exception $e) {
        // Rollback Transaction
        pg_query($conn, "ROLLBACK");
        throw $e;
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Webhook error: " . $e->getMessage());
    
    // ส่ง response error
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'order_id' => $order_id,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} finally {
    if (isset($conn)) {
        pg_close($conn);
    }
}

/**
 * ฟังก์ชันสำหรับตรวจสอบความถูกต้องของ webhook
 * (เพิ่มเติมตามความต้องการของธนาคาร)
 */
function validateWebhook($data, $signature = '') {
    // ตรวจสอบ signature ถ้าธนาคารส่งมา
    if (!empty($signature)) {
        // ตรวจสอบ HMAC หรือ signature อื่นๆ ตามที่ธนาคารกำหนด
        // $expectedSignature = hash_hmac('sha256', $data, $secretKey);
        // return hash_equals($expectedSignature, $signature);
    }
    
    // ตรวจสอบข้อมูลที่จำเป็น
    $requiredFields = ['order_id', 'amount'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return false;
        }
    }
    
    return true;
}
?>
