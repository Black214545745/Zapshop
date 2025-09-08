<?php
require_once 'config.php';

echo "<h2>🧪 ทดสอบ Payment Trigger - Auto-Update Order Status</h2>";

$message = '';
$messageType = '';
$testResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $payment_id = intval($_POST['payment_id'] ?? 0);
    $order_id = intval($_POST['order_id'] ?? 0);
    
    try {
        $conn = getConnection();
        
        switch ($action) {
            case 'test_paid':
                // ทดสอบเปลี่ยน payment_status เป็น 'paid'
                $testResults = testPaymentStatusChange($conn, $payment_id, 'paid');
                break;
                
            case 'test_failed':
                // ทดสอบเปลี่ยน payment_status เป็น 'failed'
                $testResults = testPaymentStatusChange($conn, $payment_id, 'failed');
                break;
                
            case 'test_pending':
                // ทดสอบเปลี่ยน payment_status เป็น 'pending'
                $testResults = testPaymentStatusChange($conn, $payment_id, 'pending');
                break;
                
            case 'test_multiple_payments':
                // ทดสอบกรณีมีหลาย payment ต่อ order
                $testResults = testMultiplePayments($conn, $order_id);
                break;
                
            case 'manual_update':
                // อัปเดต payment_status ด้วย SQL โดยตรง
                $newStatus = $_POST['new_status'] ?? 'paid';
                $testResults = manualUpdatePayment($conn, $payment_id, $newStatus);
                break;
        }
        
        pg_close($conn);
        
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// ฟังก์ชันทดสอบการเปลี่ยน payment_status
function testPaymentStatusChange($conn, $paymentId, $newStatus) {
    $results = [];
    
    // 1. เก็บสถานะเดิม
    $beforeQuery = "
    SELECT p.id, p.order_id, p.payment_status, p.amount,
           o.order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.id = $1
    ";
    
    $beforeResult = pg_query_params($conn, $beforeQuery, [$paymentId]);
    if (!$beforeResult || pg_num_rows($beforeResult) == 0) {
        throw new Exception('ไม่พบ payment ที่ต้องการทดสอบ');
    }
    
    $beforeData = pg_fetch_assoc($beforeResult);
    $originalPaymentStatus = $beforeData['payment_status'];
    $originalOrderStatus = $beforeData['order_status'];
    
    $results['before'] = $beforeData;
    $results['message'] = "🔍 ทดสอบเปลี่ยน payment_status จาก '{$originalPaymentStatus}' เป็น '{$newStatus}'";
    
    // 2. อัปเดต payment_status
    $updateQuery = "UPDATE payments SET payment_status = $1 WHERE id = $2";
    $updateResult = pg_query_params($conn, $updateQuery, [$newStatus, $paymentId]);
    
    if (!$updateResult) {
        throw new Exception('ไม่สามารถอัปเดต payment_status ได้: ' . pg_last_error($conn));
    }
    
    // 3. ตรวจสอบผลลัพธ์
    $afterQuery = "
    SELECT p.id, p.order_id, p.payment_status, p.amount,
           o.order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.id = $1
    ";
    
    $afterResult = pg_query_params($conn, $afterQuery, [$paymentId]);
    $afterData = pg_fetch_assoc($afterResult);
    
    $results['after'] = $afterData;
    
    // 4. ตรวจสอบว่า Trigger ทำงานหรือไม่
    if ($newStatus === 'paid' && $afterData['order_status'] === 'paid') {
        $results['trigger_working'] = true;
        $results['message'] .= " ✅ Trigger ทำงานสำเร็จ! Order status อัปเดตเป็น 'paid'";
    } elseif ($newStatus === 'failed' && $afterData['order_status'] === 'failed') {
        $results['trigger_working'] = true;
        $results['message'] .= " ✅ Trigger ทำงานสำเร็จ! Order status อัปเดตเป็น 'failed'";
    } elseif ($newStatus === 'pending' && $afterData['order_status'] === 'pending') {
        $results['trigger_working'] = true;
        $results['message'] .= " ✅ Trigger ทำงานสำเร็จ! Order status อัปเดตเป็น 'pending'";
    } else {
        $results['trigger_working'] = false;
        $results['message'] .= " ❌ Trigger ไม่ทำงาน! Order status ยังคงเป็น '{$afterData['order_status']}'";
    }
    
    // 5. คืนค่าสถานะเดิม
    $revertQuery = "UPDATE payments SET payment_status = $1 WHERE id = $2";
    pg_query_params($conn, $revertQuery, [$originalPaymentStatus, $paymentId]);
    
    $results['reverted'] = true;
    $results['message'] .= " 🔄 คืนค่าสถานะเดิมแล้ว";
    
    return $results;
}

// ฟังก์ชันทดสอบกรณีมีหลาย payment ต่อ order
function testMultiplePayments($conn, $orderId) {
    $results = [];
    
    // ตรวจสอบ payment ทั้งหมดของ order
    $paymentsQuery = "
    SELECT p.id, p.payment_status, p.amount
    FROM payments p
    WHERE p.order_id = $1
    ORDER BY p.id
    ";
    
    $paymentsResult = pg_query_params($conn, $paymentsQuery, [$orderId]);
    $payments = [];
    
    while ($row = pg_fetch_assoc($paymentsResult)) {
        $payments[] = $row;
    }
    
    $results['payments'] = $payments;
    $results['message'] = "🔍 ทดสอบกรณีมีหลาย payment ต่อ order ID: {$orderId}";
    
    // ตรวจสอบ order status
    $orderQuery = "SELECT order_status, order_number FROM orders WHERE id = $1";
    $orderResult = pg_query_params($conn, $orderQuery, [$orderId]);
    $orderData = pg_fetch_assoc($orderResult);
    
    $results['order'] = $orderData;
    $results['message'] .= " - Order Status: {$orderData['order_status']}";
    
    return $results;
}

// ฟังก์ชันอัปเดต payment_status ด้วย SQL โดยตรง
function manualUpdatePayment($conn, $paymentId, $newStatus) {
    $results = [];
    
    // เก็บสถานะเดิม
    $beforeQuery = "
    SELECT p.id, p.order_id, p.payment_status,
           o.order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.id = $1
    ";
    
    $beforeResult = pg_query_params($conn, $beforeQuery, [$paymentId]);
    $beforeData = pg_fetch_assoc($beforeResult);
    
    $results['before'] = $beforeData;
    $results['message'] = "🔧 อัปเดต payment_status เป็น '{$newStatus}' ด้วย SQL โดยตรง";
    
    // อัปเดต payment_status
    $updateQuery = "UPDATE payments SET payment_status = $1 WHERE id = $2";
    $updateResult = pg_query_params($conn, $updateQuery, [$newStatus, $paymentId]);
    
    if (!$updateResult) {
        throw new Exception('ไม่สามารถอัปเดต payment_status ได้');
    }
    
    // ตรวจสอบผลลัพธ์
    $afterQuery = "
    SELECT p.id, p.order_id, p.payment_status,
           o.order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.id = $1
    ";
    
    $afterResult = pg_query_params($conn, $afterQuery, [$paymentId]);
    $afterData = pg_fetch_assoc($afterResult);
    
    $results['after'] = $afterData;
    
    if ($afterData['order_status'] === $newStatus) {
        $results['trigger_working'] = true;
        $results['message'] .= " ✅ Trigger ทำงานสำเร็จ!";
    } else {
        $results['trigger_working'] = false;
        $results['message'] .= " ❌ Trigger ไม่ทำงาน!";
    }
    
    return $results;
}

// ดึงรายการ payments และ orders สำหรับทดสอบ
$testData = [];
try {
    $conn = getConnection();
    
    // ดึง payments ที่ pending
    $pendingQuery = "
    SELECT p.id, p.order_id, p.payment_status, p.amount,
           o.order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.payment_status = 'pending'
    ORDER BY p.created_at DESC
    LIMIT 5
    ";
    
    $pendingResult = pg_query($conn, $pendingQuery);
    while ($row = pg_fetch_assoc($pendingResult)) {
        $testData['pending'][] = $row;
    }
    
    // ดึง payments ที่ paid
    $paidQuery = "
    SELECT p.id, p.order_id, p.payment_status, p.amount,
           o.order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.payment_status = 'paid'
    ORDER BY p.created_at DESC
    LIMIT 5
    ";
    
    $paidResult = pg_query($conn, $paidQuery);
    while ($row = pg_fetch_assoc($paidResult)) {
        $testData['paid'][] = $row;
    }
    
    // ดึง orders ที่มีหลาย payments
    $multipleQuery = "
    SELECT o.id, o.order_number, o.order_status,
           COUNT(p.id) as payment_count,
           STRING_AGG(p.payment_status, ', ') as payment_statuses
    FROM orders o
    JOIN payments p ON o.id = p.order_id
    GROUP BY o.id, o.order_number, o.order_status
    HAVING COUNT(p.id) > 1
    ORDER BY o.created_at DESC
    LIMIT 5
    ";
    
    $multipleResult = pg_query($conn, $multipleQuery);
    while ($row = pg_fetch_assoc($multipleResult)) {
        $testData['multiple'][] = $row;
    }
    
    pg_close($conn);
    
} catch (Exception $e) {
    $testData = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZapShop - ทดสอบ Payment Trigger</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f8f9fa;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        h2, h3 { 
            color: #2c3e50; 
            border-bottom: 2px solid #3498db; 
            padding-bottom: 10px;
        }
        .test-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn:hover { opacity: 0.8; }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .alert-success { background: #d4edda; border-color: #28a745; color: #155724; }
        .alert-danger { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .alert-info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .result-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th { background: #e9ecef; font-weight: bold; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group select, .form-group input { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- แสดงข้อความ -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- แสดงผลการทดสอบ -->
        <?php if (!empty($testResults)): ?>
            <div class="test-section">
                <h3>📊 ผลการทดสอบ</h3>
                <div class="result-box">
                    <p><strong><?php echo $testResults['message']; ?></strong></p>
                    
                    <?php if (isset($testResults['before'])): ?>
                        <h4>📋 สถานะก่อนทดสอบ:</h4>
                        <table>
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>Payment Status</th>
                                <th>Order Status</th>
                                <th>Amount</th>
                            </tr>
                            <tr>
                                <td><?php echo $testResults['before']['id']; ?></td>
                                <td><?php echo $testResults['before']['order_id']; ?></td>
                                <td><span class="status-badge status-<?php echo $testResults['before']['payment_status']; ?>"><?php echo $testResults['before']['payment_status']; ?></span></td>
                                <td><span class="status-badge status-<?php echo $testResults['before']['order_status']; ?>"><?php echo $testResults['before']['order_status']; ?></span></td>
                                <td>฿<?php echo number_format($testResults['before']['amount'], 2); ?></td>
                            </tr>
                        </table>
                    <?php endif; ?>
                    
                    <?php if (isset($testResults['after'])): ?>
                        <h4>📋 สถานะหลังทดสอบ:</h4>
                        <table>
                            <tr>
                                <th>Payment ID</th>
                                <th>Order ID</th>
                                <th>Payment Status</th>
                                <th>Order Status</th>
                                <th>Amount</th>
                            </tr>
                            <tr>
                                <td><?php echo $testResults['after']['id']; ?></td>
                                <td><?php echo $testResults['after']['order_id']; ?></td>
                                <td><span class="status-badge status-<?php echo $testResults['after']['payment_status']; ?>"><?php echo $testResults['after']['payment_status']; ?></span></td>
                                <td><span class="status-badge status-<?php echo $testResults['after']['order_status']; ?>"><?php echo $testResults['after']['order_status']; ?></span></td>
                                <td>฿<?php echo number_format($testResults['after']['amount'], 2); ?></td>
                            </tr>
                        </table>
                    <?php endif; ?>
                    
                    <?php if (isset($testResults['trigger_working'])): ?>
                        <h4>🔍 ผลการทำงานของ Trigger:</h4>
                        <?php if ($testResults['trigger_working']): ?>
                            <p style="color: #28a745; font-weight: bold;">✅ Trigger ทำงานสำเร็จ!</p>
                        <?php else: ?>
                            <p style="color: #dc3545; font-weight: bold;">❌ Trigger ไม่ทำงาน!</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($testResults['reverted'])): ?>
                        <p style="color: #17a2b8;"><strong>🔄 คืนค่าสถานะเดิมแล้ว</strong></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ทดสอบ Trigger -->
        <div class="test-section">
            <h3>🧪 ทดสอบ Payment Trigger</h3>
            
            <!-- ทดสอบเปลี่ยน payment_status -->
            <h4>1. ทดสอบเปลี่ยน Payment Status</h4>
            <?php if (!empty($testData['pending'])): ?>
                <form method="POST" style="margin: 15px 0;">
                    <div class="form-group">
                        <label>เลือก Payment ที่ต้องการทดสอบ:</label>
                        <select name="payment_id" required>
                            <?php foreach ($testData['pending'] as $payment): ?>
                                <option value="<?php echo $payment['id']; ?>">
                                    Payment ID: <?php echo $payment['id']; ?> | 
                                    Order: <?php echo $payment['order_number']; ?> | 
                                    Amount: ฿<?php echo number_format($payment['amount'], 2); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="action" value="test_paid" class="btn btn-success">✅ ทดสอบเปลี่ยนเป็น 'paid'</button>
                    <button type="submit" name="action" value="test_failed" class="btn btn-danger">❌ ทดสอบเปลี่ยนเป็น 'failed'</button>
                </form>
            <?php else: ?>
                <p>ไม่มี payment ที่ pending ให้ทดสอบ</p>
            <?php endif; ?>
            
            <!-- ทดสอบกรณีมีหลาย payment -->
            <h4>2. ทดสอบกรณีมีหลาย Payment ต่อ Order</h4>
            <?php if (!empty($testData['multiple'])): ?>
                <form method="POST" style="margin: 15px 0;">
                    <div class="form-group">
                        <label>เลือก Order ที่มีหลาย Payment:</label>
                        <select name="order_id" required>
                            <?php foreach ($testData['multiple'] as $order): ?>
                                <option value="<?php echo $order['id']; ?>">
                                    Order: <?php echo $order['order_number']; ?> | 
                                    Payments: <?php echo $order['payment_count']; ?> | 
                                    Status: <?php echo $order['order_status']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="action" value="test_multiple_payments" class="btn btn-info">🔍 ตรวจสอบ Multiple Payments</button>
                </form>
            <?php else: ?>
                <p>ไม่มี order ที่มีหลาย payment</p>
            <?php endif; ?>
            
            <!-- อัปเดตด้วย SQL โดยตรง -->
            <h4>3. อัปเดต Payment Status ด้วย SQL โดยตรง</h4>
            <?php if (!empty($testData['pending'])): ?>
                <form method="POST" style="margin: 15px 0;">
                    <div class="form-group">
                        <label>เลือก Payment:</label>
                        <select name="payment_id" required>
                            <?php foreach ($testData['pending'] as $payment): ?>
                                <option value="<?php echo $payment['id']; ?>">
                                    Payment ID: <?php echo $payment['id']; ?> | 
                                    Order: <?php echo $payment['order_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>สถานะใหม่:</label>
                        <select name="new_status" required>
                            <option value="paid">paid</option>
                            <option value="failed">failed</option>
                            <option value="pending">pending</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="action" value="manual_update" class="btn btn-warning">🔧 อัปเดตด้วย SQL</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- ข้อมูลสำหรับทดสอบ -->
        <div class="test-section">
            <h3>📊 ข้อมูลสำหรับทดสอบ</h3>
            
            <!-- Payments ที่ Pending -->
            <h4>Payments ที่ Pending</h4>
            <?php if (!empty($testData['pending'])): ?>
                <table>
                    <tr>
                        <th>Payment ID</th>
                        <th>Order ID</th>
                        <th>Order Number</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Order Status</th>
                    </tr>
                    <?php foreach ($testData['pending'] as $payment): ?>
                        <tr>
                            <td><?php echo $payment['id']; ?></td>
                            <td><?php echo $payment['order_id']; ?></td>
                            <td><?php echo $payment['order_number']; ?></td>
                            <td>฿<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><span class="status-badge status-<?php echo $payment['payment_status']; ?>"><?php echo $payment['payment_status']; ?></span></td>
                            <td><span class="status-badge status-<?php echo $payment['order_status']; ?>"><?php echo $payment['order_status']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>ไม่มี payment ที่ pending</p>
            <?php endif; ?>
            
            <!-- Orders ที่มีหลาย Payments -->
            <h4>Orders ที่มีหลาย Payments</h4>
            <?php if (!empty($testData['multiple'])): ?>
                <table>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Number</th>
                        <th>Payment Count</th>
                        <th>Payment Statuses</th>
                        <th>Order Status</th>
                    </tr>
                    <?php foreach ($testData['multiple'] as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><?php echo $order['payment_count']; ?></td>
                            <td><?php echo $order['payment_statuses']; ?></td>
                            <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p>ไม่มี order ที่มีหลาย payment</p>
            <?php endif; ?>
        </div>

        <!-- ข้อมูลการใช้งาน -->
        <div class="test-section">
            <h3>📖 ข้อมูลการใช้งาน Trigger</h3>
            
            <h4>✅ การทำงานของ Trigger:</h4>
            <ul>
                <li><strong>payment_status = 'paid'</strong> → order_status = 'paid'</li>
                <li><strong>payment_status = 'failed'</strong> → order_status = 'failed'</li>
                <li><strong>payment_status = 'pending'</strong> → order_status = 'pending'</li>
            </ul>
            
            <h4>🔧 SQL ที่ใช้ทดสอบ:</h4>
            <pre><code>-- อัปเดต payment_status เป็น 'paid'
UPDATE payments 
SET payment_status = 'paid' 
WHERE id = [payment_id];

-- Trigger จะรันอัตโนมัติและอัปเดต order_status
-- ไม่ต้องเรียก PHP หรือ webhook เพิ่ม</code></pre>
            
            <h4>⚠️ ข้อควรระวัง:</h4>
            <ul>
                <li>Trigger จะรันทุกครั้งที่ payment_status เปลี่ยน</li>
                <li>การทดสอบจะคืนค่าสถานะเดิมอัตโนมัติ</li>
                <li>ควรทดสอบกับ DB Test ก่อน deploy จริง</li>
            </ul>
        </div>

        <!-- ปุ่มกลับ -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="create_payment_trigger.php" class="btn btn-primary">🔧 สร้าง Trigger</a>
            <a href="test_webhook.php" class="btn btn-info">🔗 ทดสอบ Webhook</a>
            <a href="index.php" class="btn btn-secondary">🏠 หน้าหลัก</a>
        </div>
    </div>
</body>
</html>
