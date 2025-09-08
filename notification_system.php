<?php
/**
 * ระบบแจ้งเตือนสำหรับการชำระเงิน
 * รองรับ LINE Notify, Email, และ SMS
 */

// ตั้งค่า CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// ฟังก์ชันสำหรับส่ง LINE Notify
function sendLineNotify($message, $token = null) {
    if (!$token) {
        $token = 'YOUR_LINE_NOTIFY_TOKEN'; // ต้องตั้งค่าให้ตรงกับ LINE Notify
    }
    
    $url = 'https://notify-api.line.me/api/notify';
    $data = [
        'message' => $message
    ];
    
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/x-www-form-urlencoded'
    ];
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'cURL Error: ' . $error
            ];
        }
        
        if ($httpCode === 200) {
            return [
                'success' => true,
                'message' => 'ส่ง LINE Notify สำเร็จ',
                'response' => $response
            ];
        } else {
            return [
                'success' => false,
                'error' => 'HTTP Error: ' . $httpCode,
                'response' => $response
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันสำหรับส่ง Email
function sendEmail($to, $subject, $message, $from = null) {
    if (!$from) {
        $from = 'noreply@zapshop.com';
    }
    
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . $from,
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    try {
        $result = mail($to, $subject, $message, implode("\r\n", $headers));
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'ส่ง Email สำเร็จ'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'ไม่สามารถส่ง Email ได้'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันสำหรับส่ง SMS (ตัวอย่าง)
function sendSMS($phone, $message) {
    // ต้องใช้ SMS Gateway จริง เช่น Twilio, Nexmo
    // ในที่นี้จะเป็นการจำลอง
    
    try {
        // บันทึก log
        $logFile = 'logs/sms.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] SMS to {$phone}: {$message}\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        return [
            'success' => true,
            'message' => 'ส่ง SMS สำเร็จ (จำลอง)',
            'phone' => $phone,
            'message' => $message
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันสำหรับสร้างข้อความแจ้งเตือน
function createNotificationMessage($type, $data) {
    switch ($type) {
        case 'payment_success':
            return "✅ การชำระเงินสำเร็จ!\n\n" .
                   "🆔 Order ID: {$data['order_id']}\n" .
                   "💰 จำนวนเงิน: ฿" . number_format($data['amount'], 2) . "\n" .
                   "📅 วันที่: " . date('d/m/Y H:i', strtotime($data['paid_at'])) . "\n" .
                   "🎉 ขอบคุณที่ใช้บริการ ZapShop!";
            
        case 'payment_failed':
            return "❌ การชำระเงินล้มเหลว!\n\n" .
                   "🆔 Order ID: {$data['order_id']}\n" .
                   "💰 จำนวนเงิน: ฿" . number_format($data['amount'], 2) . "\n" .
                   "📅 วันที่: " . date('d/m/Y H:i') . "\n" .
                   "🔧 กรุณาลองใหม่อีกครั้ง";
            
        case 'payment_pending':
            return "⏳ รอการชำระเงิน!\n\n" .
                   "🆔 Order ID: {$data['order_id']}\n" .
                   "💰 จำนวนเงิน: ฿" . number_format($data['amount'], 2) . "\n" .
                   "📅 วันที่: " . date('d/m/Y H:i') . "\n" .
                   "⏰ กรุณาชำระเงินภายใน 30 นาที";
            
        default:
            return "📢 การแจ้งเตือนจาก ZapShop\n\n" . $data['message'];
    }
}

// ฟังก์ชันหลักสำหรับส่งการแจ้งเตือน
function sendNotification($userId, $type, $data, $methods = ['line', 'email']) {
    $results = [];
    $message = createNotificationMessage($type, $data);
    
    // ส่ง LINE Notify
    if (in_array('line', $methods)) {
        $lineResult = sendLineNotify($message);
        $results['line'] = $lineResult;
    }
    
    // ส่ง Email
    if (in_array('email', $methods)) {
        // หา email ของผู้ใช้ (ต้องเชื่อมต่อกับฐานข้อมูล)
        $userEmail = 'user@example.com'; // ตัวอย่าง
        
        $emailResult = sendEmail(
            $userEmail,
            'การแจ้งเตือนจาก ZapShop',
            nl2br($message)
        );
        $results['email'] = $emailResult;
    }
    
    // ส่ง SMS
    if (in_array('sms', $methods)) {
        // หาเบอร์โทรของผู้ใช้ (ต้องเชื่อมต่อกับฐานข้อมูล)
        $userPhone = '0812345678'; // ตัวอย่าง
        
        $smsResult = sendSMS($userPhone, $message);
        $results['sms'] = $smsResult;
    }
    
    return $results;
}

// จัดการการส่งการแจ้งเตือน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data) {
        $userId = $data['user_id'] ?? '';
        $type = $data['type'] ?? '';
        $notificationData = $data['data'] ?? [];
        $methods = $data['methods'] ?? ['line', 'email'];
        
        if (!$userId || !$type) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Missing required fields'
            ]);
            exit;
        }
        
        $results = sendNotification($userId, $type, $notificationData, $methods);
        
        echo json_encode([
            'success' => true,
            'message' => 'ส่งการแจ้งเตือนเรียบร้อยแล้ว',
            'results' => $results
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
    }
} else {
    // แสดงหน้าเว็บสำหรับการทดสอบ
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ระบบแจ้งเตือน - ZapShop</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1><i class="fas fa-bell me-2"></i>ระบบแจ้งเตือน</h1>
            
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle me-2"></i>ข้อมูลระบบแจ้งเตือน</h4>
                <p>ระบบนี้รองรับการส่งการแจ้งเตือนผ่าน LINE Notify, Email, และ SMS</p>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-flask me-2"></i>ทดสอบการแจ้งเตือน</h3>
                </div>
                <div class="card-body">
                    <form id="notificationForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="userId" class="form-label">User ID</label>
                                    <input type="text" class="form-control" id="userId" value="USER001" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="type" class="form-label">ประเภทการแจ้งเตือน</label>
                                    <select class="form-select" id="type" required>
                                        <option value="payment_success">การชำระเงินสำเร็จ</option>
                                        <option value="payment_failed">การชำระเงินล้มเหลว</option>
                                        <option value="payment_pending">รอการชำระเงิน</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="orderId" class="form-label">Order ID</label>
                                    <input type="text" class="form-control" id="orderId" value="ORD001" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">จำนวนเงิน</label>
                                    <input type="number" class="form-control" id="amount" value="1000" step="0.01" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="paidAt" class="form-label">วันที่ชำระเงิน</label>
                                    <input type="datetime-local" class="form-control" id="paidAt" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">วิธีการแจ้งเตือน</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="lineNotify" checked>
                                        <label class="form-check-label" for="lineNotify">LINE Notify</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="emailNotify" checked>
                                        <label class="form-check-label" for="emailNotify">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="smsNotify">
                                        <label class="form-check-label" for="smsNotify">SMS</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>ส่งการแจ้งเตือน
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-list me-2"></i>ผลลัพธ์การทดสอบ</h3>
                </div>
                <div class="card-body">
                    <div id="testResults">
                        <p class="text-muted">ผลลัพธ์จะแสดงที่นี่หลังจากทดสอบ</p>
                    </div>
                </div>
            </div>
            
            <hr>
            <p><a href="payment_gateway_dashboard.php" class="btn btn-secondary">กลับไป Payment Gateway Dashboard</a></p>
        </div>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
        <script>
            // ตั้งค่าวันที่ปัจจุบัน
            document.getElementById('paidAt').value = new Date().toISOString().slice(0, 16);
            
            // จัดการการส่งฟอร์ม
            document.getElementById('notificationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const methods = [];
                if (document.getElementById('lineNotify').checked) methods.push('line');
                if (document.getElementById('emailNotify').checked) methods.push('email');
                if (document.getElementById('smsNotify').checked) methods.push('sms');
                
                const testData = {
                    user_id: document.getElementById('userId').value,
                    type: document.getElementById('type').value,
                    data: {
                        order_id: document.getElementById('orderId').value,
                        amount: parseFloat(document.getElementById('amount').value),
                        paid_at: document.getElementById('paidAt').value
                    },
                    methods: methods
                };
                
                // ส่งการแจ้งเตือน
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(testData)
                })
                .then(response => response.json())
                .then(data => {
                    displayResults(data);
                })
                .catch(error => {
                    displayResults({
                        success: false,
                        error: error.message
                    });
                });
            });
            
            // แสดงผลลัพธ์
            function displayResults(data) {
                const resultsDiv = document.getElementById('testResults');
                
                if (data.success) {
                    let html = '<div class="alert alert-success">';
                    html += '<h5><i class="fas fa-check-circle me-2"></i>ส่งการแจ้งเตือนสำเร็จ!</h5>';
                    html += '<p><strong>ข้อความ:</strong> ' + data.message + '</p>';
                    
                    if (data.results) {
                        html += '<h6>ผลลัพธ์:</h6>';
                        html += '<ul>';
                        for (const [method, result] of Object.entries(data.results)) {
                            const status = result.success ? '✅' : '❌';
                            const message = result.success ? result.message : result.error;
                            html += `<li><strong>${method.toUpperCase()}:</strong> ${status} ${message}</li>`;
                        }
                        html += '</ul>';
                    }
                    
                    html += '</div>';
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>เกิดข้อผิดพลาด!</h5>
                            <p><strong>ข้อผิดพลาด:</strong> ${data.error}</p>
                        </div>
                    `;
                }
            }
        </script>
    </body>
    </html>
    <?php
}
?>
