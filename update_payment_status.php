<?php
/**
 * Update Payment Status API
 * อัปเดตสถานะการชำระเงิน
 */

require_once 'config.php';
require_once 'payment_handler.php';

// ตั้งค่า header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ตรวจสอบว่าเป็นการส่งข้อมูลด้วย POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// รับข้อมูล JSON
$input = json_decode(file_get_contents('php://input'), true);

// ตรวจสอบข้อมูลที่จำเป็น
if (!isset($input['payment_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: payment_id and status']);
    exit;
}

$paymentId = intval($input['payment_id']);
$status = $input['status'];

// ตรวจสอบความถูกต้องของสถานะ
$validStatuses = ['pending', 'processing', 'completed', 'failed', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses)]);
    exit;
}

try {
    // อัปเดตสถานะการชำระเงิน
    $result = updatePaymentStatus($paymentId, $status);
    
    if ($result['success']) {
        // ดึงข้อมูลการชำระเงินเพื่อส่งกลับ
        $paymentData = getPayment($paymentId);
        
        if ($paymentData['success']) {
            $response = [
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'payment_details' => $paymentData['data']
                ]
            ];
        } else {
            $response = [
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ];
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['message']
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
