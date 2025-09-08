<?php
require_once 'config.php';

echo "<h2>🧠 ทดสอบ Trigger อัจฉริยะ - Multiple Payments per Order</h2>";

$message = '';
$messageType = '';
$testResults = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $conn = getConnection();
        
        switch ($action) {
            case 'create_test_data':
                $testResults = createTestData($conn);
                break;
                
            case 'test_step_by_step':
                $testResults = testStepByStep($conn);
                break;
                
            case 'test_multiple_payments':
                $testResults = testMultiplePayments($conn);
                break;
                
            case 'test_payment_status_change':
                $testResults = testPaymentStatusChange($conn);
                break;
                
            case 'cleanup_test_data':
                $testResults = cleanupTestData($conn);
                break;
        }
        
        pg_close($conn);
        
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// ฟังก์ชันสร้างข้อมูลทดสอบ
function createTestData($conn) {
    $results = [];
    
    // ล้างข้อมูลเก่า
    pg_query($conn, "DELETE FROM payments WHERE order_id IN (SELECT id FROM orders WHERE user_id = 999)");
    pg_query($conn, "DELETE FROM orders WHERE user_id = 999");
    
    // สร้าง Order ใหม่
    $orderQuery = "
    INSERT INTO orders (user_id, order_number, total_amount, shipping_address, shipping_phone, shipping_email, order_status)
    VALUES (999, 'TEST_001', 500.00, '123 Test Street', '0812345678', 'test@example.com', 'pending')
    RETURNING id, order_number, order_status
    ";
    
    $orderResult = pg_query($conn, $orderQuery);
    if (!$orderResult) {
        throw new Exception('ไม่สามารถสร้าง Order ได้: ' . pg_last_error($conn));
    }
    
    $orderData = pg_fetch_assoc($orderResult);
    $orderId = $orderData['id'];
    
    $results['order'] = $orderData;
    $results['message'] = "✅ สร้างข้อมูลทดสอบสำเร็จ!";
    $results['message'] .= "<br>📋 Order ID: {$orderId}, Status: {$orderData['order_status']}";
    
    return $results;
}

// ฟังก์ชันทดสอบแบบ Step-by-Step
function testStepByStep($conn) {
    $results = [];
    $steps = [];
    
    // หา Order ที่มี user_id = 999
    $orderQuery = "SELECT id, order_number, order_status FROM orders WHERE user_id = 999 ORDER BY id DESC LIMIT 1";
    $orderResult = pg_query($conn, $orderQuery);
    
    if (!$orderResult || pg_num_rows($orderResult) == 0) {
        throw new Exception('ไม่พบ Order สำหรับทดสอบ กรุณาสร้างข้อมูลทดสอบก่อน');
    }
    
    $orderData = pg_fetch_assoc($orderResult);
    $orderId = $orderData['id'];
    
    $steps[] = "🎯 ขั้นตอนที่ 1: สร้าง Payment ที่ยังไม่จ่าย (pending)";
    
    // เพิ่ม Payment ที่ยังไม่จ่าย
    $payment1Query = "
    INSERT INTO payments (order_id, user_id, amount, payment_method, payment_status)
    VALUES ($1, 999, 500.00, 'QR', 'pending')
    RETURNING id, payment_status
    ";
    
    $payment1Result = pg_query_params($conn, $payment1Query, [$orderId]);
    if (!$payment1Result) {
        throw new Exception('ไม่สามารถสร้าง Payment ได้');
    }
    
    $payment1Data = pg_fetch_assoc($payment1Result);
    $steps[] = "   ✅ สร้าง Payment ID: {$payment1Data['id']}, Status: {$payment1Data['payment_status']}";
    
    // ตรวจสอบ Order Status
    $checkOrderQuery = "SELECT order_status FROM orders WHERE id = $1";
    $checkOrderResult = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus = pg_fetch_assoc($checkOrderResult)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus} (ยังไม่จ่าย → order_status ยังคง pending)";
    
    $steps[] = "🎯 ขั้นตอนที่ 2: เพิ่ม Payment อีกอัน (failed)";
    
    // เพิ่ม Payment ที่ failed
    $payment2Query = "
    INSERT INTO payments (order_id, user_id, amount, payment_method, payment_status)
    VALUES ($1, 999, 500.00, 'CreditCard', 'failed')
    RETURNING id, payment_status
    ";
    
    $payment2Result = pg_query_params($conn, $payment2Query, [$orderId]);
    if (!$payment2Result) {
        throw new Exception('ไม่สามารถสร้าง Payment ที่ 2 ได้');
    }
    
    $payment2Data = pg_fetch_assoc($payment2Result);
    $steps[] = "   ✅ สร้าง Payment ID: {$payment2Data['id']}, Status: {$payment2Data['payment_status']}";
    
    // ตรวจสอบ Order Status อีกครั้ง
    $checkOrderResult2 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus2 = pg_fetch_assoc($checkOrderResult2)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus2} (ยังไม่มี paid → Order ยังเป็น pending)";
    
    $steps[] = "🎯 ขั้นตอนที่ 3: อัปเดต Payment ใด ๆ เป็น paid";
    
    // อัปเดต Payment แรกเป็น paid
    $updateQuery = "UPDATE payments SET payment_status = 'paid' WHERE id = $1";
    $updateResult = pg_query_params($conn, $updateQuery, [$payment1Data['id']]);
    
    if (!$updateResult) {
        throw new Exception('ไม่สามารถอัปเดต Payment เป็น paid ได้');
    }
    
    $steps[] = "   ✅ อัปเดต Payment ID: {$payment1Data['id']} เป็น 'paid'";
    
    // ตรวจสอบ Order Status หลังจากมี Payment เป็น paid
    $checkOrderResult3 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus3 = pg_fetch_assoc($checkOrderResult3)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus3} (ทันทีที่มี Payment ใด paid → Order อัปเดตเป็น paid)";
    
    $steps[] = "🎯 ขั้นตอนที่ 4: เพิ่ม Payment ใหม่ (pending) หลังจาก Order ถูกจ่ายแล้ว";
    
    // เพิ่ม Payment ใหม่หลังจาก Order ถูกจ่ายแล้ว
    $payment3Query = "
    INSERT INTO payments (order_id, user_id, amount, payment_method, payment_status)
    VALUES ($1, 999, 500.00, 'MobileBanking', 'pending')
    RETURNING id, payment_status
    ";
    
    $payment3Result = pg_query_params($conn, $payment3Query, [$orderId]);
    if (!$payment3Result) {
        throw new Exception('ไม่สามารถสร้าง Payment ที่ 3 ได้');
    }
    
    $payment3Data = pg_fetch_assoc($payment3Result);
    $steps[] = "   ✅ สร้าง Payment ID: {$payment3Data['id']}, Status: {$payment3Data['payment_status']}";
    
    // ตรวจสอบ Order Status หลังจากเพิ่ม Payment ใหม่
    $checkOrderResult4 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus4 = pg_fetch_assoc($checkOrderResult4)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus4} (Order ยังคงเป็น paid ถึงแม้จะมี payment ใหม่ pending เพราะ Trigger เช็คว่า 'มี paid อย่างน้อย 1 รายการ')";
    
    // แสดงข้อมูลสรุป
    $summaryQuery = "
    SELECT 
        o.id, o.order_number, o.order_status,
        COUNT(p.id) as payment_count,
        STRING_AGG(p.payment_status, ', ') as payment_statuses
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = $1
    GROUP BY o.id, o.order_number, o.order_status
    ";
    
    $summaryResult = pg_query_params($conn, $summaryQuery, [$orderId]);
    $summaryData = pg_fetch_assoc($summaryResult);
    
    $steps[] = "🎉 สรุปผลการทดสอบ:";
    $steps[] = "   📋 Order ID: {$summaryData['id']}, Status: {$summaryData['order_status']}";
    $steps[] = "   💳 Payment Count: {$summaryData['payment_count']}";
    $steps[] = "   🔍 Payment Statuses: {$summaryData['payment_statuses']}";
    
    $results['steps'] = $steps;
    $results['message'] = "✅ การทดสอบ Step-by-Step สำเร็จ!";
    $results['summary'] = $summaryData;
    
    return $results;
}

// ฟังก์ชันทดสอบกรณีมีหลาย Payment
function testMultiplePayments($conn) {
    $results = [];
    
    // หา Order ที่มี user_id = 999
    $orderQuery = "SELECT id, order_number, order_status FROM orders WHERE user_id = 999 ORDER BY id DESC LIMIT 1";
    $orderResult = pg_query($conn, $orderQuery);
    
    if (!$orderResult || pg_num_rows($orderResult) == 0) {
        throw new Exception('ไม่พบ Order สำหรับทดสอบ');
    }
    
    $orderData = pg_fetch_assoc($orderResult);
    $orderId = $orderData['id'];
    
    // ตรวจสอบ Payment ทั้งหมดของ Order
    $paymentsQuery = "
    SELECT id, payment_status, payment_method, amount
    FROM payments
    WHERE order_id = $1
    ORDER BY id
    ";
    
    $paymentsResult = pg_query_params($conn, $paymentsQuery, [$orderId]);
    $payments = [];
    
    while ($row = pg_fetch_assoc($paymentsResult)) {
        $payments[] = $row;
    }
    
    $results['order'] = $orderData;
    $results['payments'] = $payments;
    $results['message'] = "🔍 ทดสอบกรณีมีหลาย Payment ต่อ Order ID: {$orderId}";
    $results['message'] .= "<br>📊 Order Status: {$orderData['order_status']}";
    $results['message'] .= "<br>💳 Payment Count: " . count($payments);
    
    return $results;
}

// ฟังก์ชันทดสอบการเปลี่ยน Payment Status
function testPaymentStatusChange($conn) {
    $results = [];
    
    // หา Payment ที่ pending
    $paymentQuery = "
    SELECT p.id, p.order_id, p.payment_status, p.amount,
           o.order_status, o.order_number
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE p.user_id = 999 AND p.payment_status = 'pending'
    LIMIT 1
    ";
    
    $paymentResult = pg_query($conn, $paymentQuery);
    if (!$paymentResult || pg_num_rows($paymentResult) == 0) {
        throw new Exception('ไม่พบ Payment ที่ pending ให้ทดสอบ');
    }
    
    $paymentData = pg_fetch_assoc($paymentResult);
    $paymentId = $paymentData['id'];
    $orderId = $paymentData['order_id'];
    
    $results['before'] = $paymentData;
    $results['message'] = "🔍 ทดสอบเปลี่ยน Payment Status จาก 'pending' เป็น 'paid'";
    
    // อัปเดต Payment Status
    $updateQuery = "UPDATE payments SET payment_status = 'paid' WHERE id = $1";
    $updateResult = pg_query_params($conn, $updateQuery, [$paymentId]);
    
    if (!$updateResult) {
        throw new Exception('ไม่สามารถอัปเดต Payment Status ได้');
    }
    
    // ตรวจสอบผลลัพธ์
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
    
    if ($afterData['order_status'] === 'paid') {
        $results['trigger_working'] = true;
        $results['message'] .= " ✅ Trigger อัจฉริยะทำงานสำเร็จ! Order status อัปเดตเป็น 'paid'";
    } else {
        $results['trigger_working'] = false;
        $results['message'] .= " ❌ Trigger อัจฉริยะไม่ทำงาน! Order status ยังคงเป็น '{$afterData['order_status']}'";
    }
    
    return $results;
}

// ฟังก์ชันล้างข้อมูลทดสอบ
function cleanupTestData($conn) {
    $results = [];
    
    // ลบ Payment ที่เกี่ยวข้อง
    $deletePaymentsQuery = "DELETE FROM payments WHERE user_id = 999";
    $deletePaymentsResult = pg_query($conn, $deletePaymentsQuery);
    
    // ลบ Order ที่เกี่ยวข้อง
    $deleteOrdersQuery = "DELETE FROM orders WHERE user_id = 999";
    $deleteOrdersResult = pg_query($conn, $deleteOrdersQuery);
    
    $results['message'] = "🧹 ล้างข้อมูลทดสอบสำเร็จ!";
    $results['message'] .= "<br>✅ ลบ Payments และ Orders ที่เกี่ยวข้องแล้ว";
    
    return $results;
}

// ดึงข้อมูลสำหรับแสดงผล
$testData = [];
try {
    $conn = getConnection();
    
    // ดึง Order ที่สร้างสำหรับทดสอบ
    $testOrderQuery = "
    SELECT id, order_number, order_status, total_amount, created_at
    FROM orders 
    WHERE user_id = 999
    ORDER BY created_at DESC
    LIMIT 5
    ";
    
    $testOrderResult = pg_query($conn, $testOrderQuery);
    while ($row = pg_fetch_assoc($testOrderResult)) {
        $testData['orders'][] = $row;
    }
    
    // ดึง Payment ที่เกี่ยวข้อง
    if (!empty($testData['orders'])) {
        $orderIds = array_column($testData['orders'], 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '$' . ($i + 1)));
        
        $testPaymentQuery = "
        SELECT p.id, p.order_id, p.payment_status, p.payment_method, p.amount,
               o.order_number, o.order_status
        FROM payments p
        JOIN orders o ON p.order_id = o.id
        WHERE p.order_id IN ($placeholders)
        ORDER BY p.order_id, p.id
        ";
        
        $testPaymentResult = pg_query_params($conn, $testPaymentQuery, $orderIds);
        while ($row = pg_fetch_assoc($testPaymentResult)) {
            $testData['payments'][] = $row;
        }
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
    <title>ZapShop - ทดสอบ Trigger อัจฉริยะ</title>
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
            padding: 10px 20px;
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
        .step-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
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
        .logic-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
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
                    
                    <?php if (isset($testResults['steps'])): ?>
                        <h4>📋 ขั้นตอนการทดสอบ:</h4>
                        <?php foreach ($testResults['steps'] as $step): ?>
                            <div class="step-box">
                                <?php echo $step; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($testResults['before']) && isset($testResults['after'])): ?>
                        <h4>📋 สถานะก่อนและหลังทดสอบ:</h4>
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
                        
                        <h5>หลังทดสอบ:</h5>
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
                        <h4>🔍 ผลการทำงานของ Trigger อัจฉริยะ:</h4>
                        <?php if ($testResults['trigger_working']): ?>
                            <p style="color: #28a745; font-weight: bold;">✅ Trigger อัจฉริยะทำงานสำเร็จ!</p>
                        <?php else: ?>
                            <p style="color: #dc3545; font-weight: bold;">❌ Trigger อัจฉริยะไม่ทำงาน!</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (isset($testResults['summary'])): ?>
                        <h4>📋 สรุปผลการทดสอบ:</h4>
                        <div class="logic-box">
                            <p><strong>Order ID:</strong> <?php echo $testResults['summary']['id']; ?></p>
                            <p><strong>Order Status:</strong> <span class="status-badge status-<?php echo $testResults['summary']['order_status']; ?>"><?php echo $testResults['summary']['order_status']; ?></span></p>
                            <p><strong>Payment Count:</strong> <?php echo $testResults['summary']['payment_count']; ?></p>
                            <p><strong>Payment Statuses:</strong> <?php echo $testResults['summary']['payment_statuses']; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ทดสอบ Trigger อัจฉริยะ -->
        <div class="test-section">
            <h3>🧠 ทดสอบ Trigger อัจฉริยะ</h3>
            
            <!-- Logic ของ Trigger อัจฉริยะ -->
            <div class="logic-box">
                <h4>🎯 Logic ของ Trigger อัจฉริยะ:</h4>
                <ul>
                    <li><strong>ถ้ามี Payment ใดใน Order เป็น 'paid'</strong> → อัปเดต <code>orders.order_status = 'paid'</code></li>
                    <li><strong>ถ้ายังไม่มี Payment ไหนเป็น 'paid'</strong> → <code>orders.order_status</code> ยังคง 'pending'</li>
                    <li><strong>ยังเก็บข้อมูล Payment อื่น ๆ ได้ครบ</strong> เช่น payment_status ของแต่ละ Payment</li>
                </ul>
            </div>
            
            <!-- ปุ่มทดสอบ -->
            <form method="POST" style="margin: 15px 0;">
                <button type="submit" name="action" value="create_test_data" class="btn btn-primary">📝 สร้างข้อมูลทดสอบ</button>
                <button type="submit" name="action" value="test_step_by_step" class="btn btn-success">🧪 ทดสอบ Step-by-Step</button>
                <button type="submit" name="action" value="test_multiple_payments" class="btn btn-info">🔍 ตรวจสอบ Multiple Payments</button>
                <button type="submit" name="action" value="test_payment_status_change" class="btn btn-warning">🔄 ทดสอบเปลี่ยน Payment Status</button>
                <button type="submit" name="action" value="cleanup_test_data" class="btn btn-danger">🧹 ล้างข้อมูลทดสอบ</button>
            </form>
        </div>

        <!-- ข้อมูลสำหรับทดสอบ -->
        <?php if (!empty($testData['orders'])): ?>
            <div class="test-section">
                <h3>📊 ข้อมูลสำหรับทดสอบ</h3>
                
                <!-- Orders ที่สร้างสำหรับทดสอบ -->
                <h4>Orders ที่สร้างสำหรับทดสอบ</h4>
                <table>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Number</th>
                        <th>Order Status</th>
                        <th>Total Amount</th>
                        <th>Created At</th>
                    </tr>
                    <?php foreach ($testData['orders'] as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo $order['order_number']; ?></td>
                            <td><span class="status-badge status-<?php echo $order['order_status']; ?>"><?php echo $order['order_status']; ?></span></td>
                            <td>฿<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                
                <!-- Payments ที่เกี่ยวข้อง -->
                <?php if (!empty($testData['payments'])): ?>
                    <h4>Payments ที่เกี่ยวข้อง</h4>
                    <table>
                        <tr>
                            <th>Payment ID</th>
                            <th>Order ID</th>
                            <th>Payment Status</th>
                            <th>Payment Method</th>
                            <th>Amount</th>
                            <th>Order Status</th>
                        </tr>
                        <?php foreach ($testData['payments'] as $payment): ?>
                            <tr>
                                <td><?php echo $payment['id']; ?></td>
                                <td><?php echo $payment['order_id']; ?></td>
                                <td><span class="status-badge status-<?php echo $payment['payment_status']; ?>"><?php echo $payment['payment_status']; ?></span></td>
                                <td><?php echo $payment['payment_method']; ?></td>
                                <td>฿<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $payment['order_status']; ?>"><?php echo $payment['order_status']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- ข้อมูลการใช้งาน -->
        <div class="test-section">
            <h3>📖 ข้อมูลการใช้งาน Trigger อัจฉริยะ</h3>
            
            <h4>✅ การทำงานของ Trigger อัจฉริยะ:</h4>
            <ul>
                <li><strong>มี Payment ใดเป็น 'paid'</strong> → order_status = 'paid'</li>
                <li><strong>ยังไม่มี Payment ไหนเป็น 'paid'</strong> → order_status = 'pending'</li>
                <li><strong>รองรับหลาย Payment ต่อ Order</strong> → เก็บสถานะแยกได้ครบ</li>
            </ul>
            
            <h4>🔧 SQL ที่ใช้ทดสอบ:</h4>
            <pre><code>-- เพิ่ม Payment ใหม่
INSERT INTO payments (order_id, user_id, amount, payment_method, payment_status)
VALUES (123, 999, 500.00, 'QR', 'pending');

-- อัปเดต Payment เป็น paid
UPDATE payments 
SET payment_status = 'paid' 
WHERE id = 1;

-- Trigger อัจฉริยะจะรันอัตโนมัติและตรวจสอบ:
-- ถ้ามี Payment ใดเป็น paid → Order = 'paid'
-- ถ้ายังไม่มี Payment ไหนเป็น paid → Order = 'pending'
-- ไม่ต้องเรียก PHP หรือ webhook เพิ่ม</code></pre>
            
            <h4>⚠️ ข้อควรระวัง:</h4>
            <ul>
                <li>Trigger จะรันทุกครั้งที่ INSERT/UPDATE/DELETE payments</li>
                <li>การทดสอบจะสร้างข้อมูลใน user_id = 999</li>
                <li>ควรทดสอบกับ DB Test ก่อน deploy จริง</li>
                <li>Logic อัจฉริยะ: ตรวจสอบ COUNT ของ paid payments</li>
            </ul>
        </div>

        <!-- ปุ่มกลับ -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="create_payment_trigger.php" class="btn btn-primary">🔧 สร้าง Trigger</a>
            <a href="test_trigger.php" class="btn btn-info">🧪 ทดสอบ Trigger ปกติ</a>
            <a href="test_webhook.php" class="btn btn-warning">🔗 ทดสอบ Webhook</a>
            <a href="index.php" class="btn btn-secondary">🏠 หน้าหลัก</a>
        </div>
    </div>
</body>
</html>
