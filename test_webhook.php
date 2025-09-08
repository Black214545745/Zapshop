<?php
require_once 'config.php';

// ตรวจสอบว่าเป็น admin หรือไม่ (ควรเพิ่มการตรวจสอบสิทธิ์)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin only.");
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $test_type = $_POST['test_type'] ?? 'success';
    
    if ($order_id <= 0 || $amount <= 0) {
        $message = 'กรุณากรอกข้อมูลให้ถูกต้อง';
        $messageType = 'danger';
    } else {
        try {
            // สร้างข้อมูล webhook จำลอง
            $webhookData = [
                'order_id' => $order_id,
                'amount' => $amount,
                'transaction_id' => 'TEST_' . uniqid(),
                'payment_date' => date('Y-m-d H:i:s'),
                'status' => 'success',
                'test_mode' => true
            ];
            
            // ส่ง webhook ไปยังระบบ
            $webhookUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook.php';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: TestWebhook/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('CURL Error: ' . $error);
            }
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if ($responseData && $responseData['status'] === 'success') {
                    $message = 'Webhook ทดสอบสำเร็จ! Order ID: ' . $order_id . ' ได้รับการอัปเดตแล้ว';
                    $messageType = 'success';
                } else {
                    $message = 'Webhook ได้รับการตอบกลับแต่ไม่สำเร็จ: ' . $response;
                    $messageType = 'warning';
                }
            } else {
                $message = 'Webhook ทดสอบล้มเหลว. HTTP Code: ' . $httpCode . ', Response: ' . $response;
                $messageType = 'danger';
            }
            
        } catch (Exception $e) {
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// ดึงรายการ orders ที่รอการชำระเงิน
$pendingOrders = [];
try {
    $conn = getConnection();
    $query = "SELECT o.id, o.order_number, o.total_amount, o.order_status, o.created_at,
                     p.payment_status, p.amount
              FROM orders o 
              LEFT JOIN payments p ON o.id = p.order_id 
              WHERE o.order_status = 'pending'
              ORDER BY o.created_at DESC 
              LIMIT 10";
    
    $result = pg_query($conn, $query);
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $pendingOrders[] = $row;
        }
    }
    pg_close($conn);
} catch (Exception $e) {
    $pendingOrders = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZapShop - ทดสอบ Webhook</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/zapshop-design-system.css" rel="stylesheet">
    <style>
        .test-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 40px 0;
        }
        .test-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .webhook-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .order-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <!-- หัวข้อ -->
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-cogs"></i> ทดสอบ Webhook ระบบการชำระเงิน</h2>
                        <p class="text-muted">สำหรับทดสอบระบบการรับ callback จากธนาคาร</p>
                    </div>

                    <!-- แสดงข้อความ -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- ข้อมูล Webhook -->
                    <div class="test-card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> ข้อมูล Webhook</h5>
                        </div>
                        <div class="card-body">
                            <div class="webhook-info">
                                <h6>Webhook URL:</h6>
                                <code><?php echo 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/webhook.php'; ?></code>
                                
                                <h6 class="mt-3">วิธีการใช้งาน:</h6>
                                <ol>
                                    <li>ธนาคารจะส่ง POST request มาที่ URL นี้</li>
                                    <li>ข้อมูลจะถูกส่งในรูปแบบ JSON</li>
                                    <li>ระบบจะอัปเดตสถานะการชำระเงินอัตโนมัติ</li>
                                    <li>ส่ง response กลับไปยังธนาคาร</li>
                                </ol>
                                
                                <h6 class="mt-3">ข้อมูลที่ธนาคารส่งมา:</h6>
                                <pre><code>{
    "order_id": "123",
    "amount": "599.00",
    "transaction_id": "TXN123456",
    "payment_date": "2024-01-15 14:30:00",
    "status": "success"
}</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- ทดสอบ Webhook -->
                    <div class="test-card">
                        <div class="card-header">
                            <h5><i class="fas fa-play-circle"></i> ทดสอบ Webhook</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Order ID:</label>
                                        <input type="number" name="order_id" class="form-control" required 
                                               placeholder="กรอก Order ID ที่ต้องการทดสอบ">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">จำนวนเงิน:</label>
                                        <input type="number" name="amount" class="form-control" step="0.01" required 
                                               placeholder="กรอกจำนวนเงิน">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">ประเภทการทดสอบ:</label>
                                        <select name="test_type" class="form-select">
                                            <option value="success">ชำระเงินสำเร็จ</option>
                                            <option value="failed">ชำระเงินล้มเหลว</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> ส่ง Webhook ทดสอบ
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- รายการ Orders ที่รอการชำระเงิน -->
                    <div class="test-card">
                        <div class="card-header">
                            <h5><i class="fas fa-clock"></i> Orders ที่รอการชำระเงิน (10 รายการล่าสุด)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($pendingOrders)): ?>
                                <?php foreach ($pendingOrders as $order): ?>
                                    <div class="order-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <strong>Order ID:</strong> <?php echo $order['id']; ?><br>
                                                <small class="text-muted"><?php echo $order['order_number']; ?></small>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>ยอดรวม:</strong> ฿<?php echo number_format($order['total_amount'], 2); ?><br>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></small>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>สถานะ:</strong> 
                                                <span class="badge badge-<?php echo $order['order_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo $order['order_status']; ?>
                                                </span>
                                            </div>
                                            <div class="col-md-3">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="fillTestForm(<?php echo $order['id']; ?>, <?php echo $order['total_amount']; ?>)">
                                                    <i class="fas fa-edit"></i> ใช้ข้อมูลนี้ทดสอบ
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center">ไม่มี Orders ที่รอการชำระเงิน</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ข้อมูลการทดสอบ -->
                    <div class="test-card">
                        <div class="card-header">
                            <h5><i class="fas fa-file-alt"></i> ข้อมูลการทดสอบ</h5>
                        </div>
                        <div class="card-body">
                            <h6>ขั้นตอนการทดสอบ:</h6>
                            <ol>
                                <li>เลือก Order ID จากรายการด้านบน หรือกรอกข้อมูลเอง</li>
                                <li>กรอกจำนวนเงินที่ต้องการทดสอบ</li>
                                <li>กดปุ่ม "ส่ง Webhook ทดสอบ"</li>
                                <li>ระบบจะจำลองการส่ง webhook จากธนาคาร</li>
                                <li>ตรวจสอบผลลัพธ์และสถานะในฐานข้อมูล</li>
                            </ol>
                            
                            <h6 class="mt-3">การตรวจสอบผลลัพธ์:</h6>
                            <ul>
                                <li>ตรวจสอบสถานะในตาราง <code>orders.order_status</code></li>
                                <li>ตรวจสอบสถานะในตาราง <code>payments.payment_status</code></li>
                                <li>ตรวจสอบ log ใน <code>error_log</code> ของ PHP</li>
                                <li>ตรวจสอบ response ที่ได้รับจาก webhook</li>
                            </ul>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>หมายเหตุ:</strong> การทดสอบนี้จะอัปเดตฐานข้อมูลจริง 
                                กรุณาใช้เฉพาะในระบบทดสอบเท่านั้น
                            </div>
                        </div>
                    </div>

                    <!-- ปุ่มกลับ -->
                    <div class="text-center mt-4">
                        <a href="admin-dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> กลับไปหน้า Admin
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> หน้าหลัก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // กรอกข้อมูลในฟอร์มทดสอบ
        function fillTestForm(orderId, amount) {
            document.querySelector('input[name="order_id"]').value = orderId;
            document.querySelector('input[name="amount"]').value = amount;
            
            // เลื่อนไปยังฟอร์ม
            document.querySelector('.test-card:nth-child(3)').scrollIntoView({
                behavior: 'smooth'
            });
        }
        
        // Auto-refresh ข้อมูลทุก 30 วินาที
        setInterval(() => {
            if (window.location.href.includes('test_webhook.php')) {
                window.location.reload();
            }
        }, 30000);
    </script>

    <script src="assets/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
</body>
</html>
