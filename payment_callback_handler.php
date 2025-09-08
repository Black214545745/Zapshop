<?php
/**
 * Payment Callback Handler
 * รับข้อมูลการชำระเงินจาก Payment Gateway
 */

session_start();
require_once 'config.php';
require_once 'payment_handler.php';

// ตั้งค่า CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// ฟังก์ชันสำหรับบันทึก log
function logCallback($data) {
    $logFile = 'logs/payment_callback.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// ฟังก์ชันสำหรับตรวจสอบ signature
function verifySignature($data, $signature, $secret) {
    // สร้าง signature จากข้อมูลที่ได้รับ
    $expectedSignature = hash_hmac('sha256', json_encode($data), $secret);
    
    // เปรียบเทียบ signature
    return hash_equals($expectedSignature, $signature);
}

// ฟังก์ชันสำหรับอัปเดตสถานะการชำระเงิน
function updatePaymentStatus($transactionId, $status, $amount, $paidAt = null) {
    global $pdo;
    
    try {
        // อัปเดตตาราง payments
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = ?, updated_at = NOW() 
            WHERE transaction_id = ?
        ");
        $stmt->execute([$status, $transactionId]);
        
        // อัปเดตตาราง orders
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = ?, updated_at = NOW() 
            WHERE id = (
                SELECT order_id FROM payments WHERE transaction_id = ?
            )
        ");
        $stmt->execute([$status, $transactionId]);
        
        // บันทึก log
        logCallback([
            'action' => 'update_payment_status',
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'result' => 'success'
        ]);
        
        return true;
    } catch (Exception $e) {
        logCallback([
            'action' => 'update_payment_status',
            'transaction_id' => $transactionId,
            'status' => $status,
            'error' => $e->getMessage(),
            'result' => 'failed'
        ]);
        
        return false;
    }
}

// ฟังก์ชันสำหรับส่งการแจ้งเตือน
function sendNotification($userId, $message, $type = 'payment') {
    // ส่งการแจ้งเตือนไปยังผู้ใช้
    // สามารถใช้ LINE Notify, Email, หรือ SMS ได้
    logCallback([
        'action' => 'send_notification',
        'user_id' => $userId,
        'message' => $message,
        'type' => $type
    ]);
}

// จัดการ callback จาก Payment Gateway
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับข้อมูล JSON
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // บันทึก log
    logCallback([
        'action' => 'callback_received',
        'method' => 'POST',
        'data' => $data,
        'headers' => getallheaders()
    ]);
    
    if ($data) {
        // ตรวจสอบ signature (ถ้ามี)
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        $secret = 'your_webhook_secret'; // ต้องตั้งค่าให้ตรงกับ Payment Gateway
        
        if ($signature && !verifySignature($data, $signature, $secret)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid signature'
            ]);
            exit;
        }
        
        // ตรวจสอบข้อมูลที่จำเป็น
        $transactionId = $data['transaction_id'] ?? '';
        $status = $data['status'] ?? '';
        $amount = $data['amount'] ?? 0;
        $orderId = $data['order_id'] ?? '';
        $paidAt = $data['paid_at'] ?? null;
        
        if (!$transactionId || !$status) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required fields'
            ]);
            exit;
        }
        
        // อัปเดตสถานะการชำระเงิน
        $updateResult = updatePaymentStatus($transactionId, $status, $amount, $paidAt);
        
        if ($updateResult) {
            // ส่งการแจ้งเตือนไปยังผู้ใช้
            if ($status === 'success' || $status === 'completed') {
                // หา user_id จาก order_id
                try {
                    global $pdo;
                    $stmt = $pdo->prepare("SELECT user_id FROM orders WHERE id = ?");
                    $stmt->execute([$orderId]);
                    $order = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($order) {
                        $message = "การชำระเงินสำเร็จ! จำนวนเงิน: ฿" . number_format($amount, 2);
                        sendNotification($order['user_id'], $message, 'payment_success');
                    }
                } catch (Exception $e) {
                    logCallback([
                        'action' => 'get_user_id',
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // ส่ง response สำเร็จ
            echo json_encode([
                'success' => true,
                'message' => 'Payment status updated successfully',
                'transaction_id' => $transactionId,
                'status' => $status
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update payment status'
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
    }
} else {
    // แสดงข้อมูลสำหรับการทดสอบ
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Callback Handler - ZapShop</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1><i class="fas fa-webhook me-2"></i>Payment Callback Handler</h1>
            
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle me-2"></i>ข้อมูล Callback URL</h4>
                <p><strong>URL:</strong> <code><?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?></code></p>
                <p><strong>Method:</strong> POST</p>
                <p><strong>Content-Type:</strong> application/json</p>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-flask me-2"></i>ทดสอบ Callback</h3>
                </div>
                <div class="card-body">
                    <p>ใช้ข้อมูลนี้เพื่อทดสอบ Callback:</p>
                    <pre class="bg-light p-3 rounded">
{
    "transaction_id": "TXN123456789",
    "status": "success",
    "amount": 1000.00,
    "order_id": "ORD001",
    "paid_at": "2024-01-15T10:30:00Z"
}
                    </pre>
                    
                    <button class="btn btn-primary" onclick="testCallback()">
                        <i class="fas fa-play me-2"></i>ทดสอบ Callback
                    </button>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-list me-2"></i>Log ล่าสุด</h3>
                </div>
                <div class="card-body">
                    <div id="logContent">
                        <p class="text-muted">กำลังโหลด log...</p>
                    </div>
                </div>
            </div>
            
            <hr>
            <p><a href="payment_gateway_dashboard.php" class="btn btn-secondary">กลับไป Payment Gateway Dashboard</a></p>
        </div>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        <script>
            // ทดสอบ Callback
            function testCallback() {
                const testData = {
                    transaction_id: "TXN" + Date.now(),
                    status: "success",
                    amount: 1000.00,
                    order_id: "ORD001",
                    paid_at: new Date().toISOString()
                };
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Signature': 'test_signature'
                    },
                    body: JSON.stringify(testData)
                })
                .then(response => response.json())
                .then(data => {
                    alert('ผลลัพธ์: ' + JSON.stringify(data, null, 2));
                    loadLogs();
                })
                .catch(error => {
                    alert('เกิดข้อผิดพลาด: ' + error.message);
                });
            }
            
            // โหลด Log
            function loadLogs() {
                // ในที่นี้จะแสดง log จากไฟล์
                document.getElementById('logContent').innerHTML = '<p class="text-muted">Log จะแสดงที่นี่หลังจากทดสอบ</p>';
            }
            
            // โหลด Log เมื่อเปิดหน้า
            loadLogs();
        </script>
    </body>
    </html>
    <?php
}
?>
