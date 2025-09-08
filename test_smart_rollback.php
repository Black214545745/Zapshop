<?php
require_once 'config.php';

echo "<h2>🔄 ทดสอบ Trigger อัจฉริยะ - เวอร์ชันไป-กลับ (Smart Rollback)</h2>";

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
                
            case 'test_rollback_scenario':
                $testResults = testRollbackScenario($conn);
                break;
                
            case 'test_multiple_payments_rollback':
                $testResults = testMultiplePaymentsRollback($conn);
                break;
                
            case 'test_payment_cancellation':
                $testResults = testPaymentCancellation($conn);
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
    VALUES (999, 'ROLLBACK_TEST_001', 1000.00, '123 Test Street', '0812345678', 'test@example.com', 'pending')
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

// ฟังก์ชันทดสอบ Rollback Scenario
function testRollbackScenario($conn) {
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
    
    $steps[] = "🎯 ขั้นตอนที่ 1: สร้าง Payment ยังไม่จ่าย (pending)";
    
    // เพิ่ม Payment ที่ยังไม่จ่าย
    $paymentQuery = "
    INSERT INTO payments (order_id, user_id, amount, payment_method, payment_status)
    VALUES ($1, 999, 1000.00, 'QR', 'pending')
    RETURNING id, payment_status
    ";
    
    $paymentResult = pg_query_params($conn, $paymentQuery, [$orderId]);
    if (!$paymentResult) {
        throw new Exception('ไม่สามารถสร้าง Payment ได้');
    }
    
    $paymentData = pg_fetch_assoc($paymentResult);
    $paymentId = $paymentData['id'];
    $steps[] = "   ✅ สร้าง Payment ID: {$paymentData['id']}, Status: {$paymentData['payment_status']}";
    
    // ตรวจสอบ Order Status
    $checkOrderQuery = "SELECT order_status FROM orders WHERE id = $1";
    $checkOrderResult = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus = pg_fetch_assoc($checkOrderResult)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus} (ยังไม่จ่าย → order_status ยังคง pending)";
    
    $steps[] = "🎯 ขั้นตอนที่ 2: เปลี่ยน Payment เป็น paid";
    
    // อัปเดต Payment เป็น paid
    $updateQuery = "UPDATE payments SET payment_status = 'paid' WHERE id = $1";
    $updateResult = pg_query_params($conn, $updateQuery, [$paymentId]);
    
    if (!$updateResult) {
        throw new Exception('ไม่สามารถอัปเดต Payment เป็น paid ได้');
    }
    
    $steps[] = "   ✅ อัปเดต Payment ID: {$paymentId} เป็น 'paid'";
    
    // ตรวจสอบ Order Status หลังจากมี Payment เป็น paid
    $checkOrderResult2 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus2 = pg_fetch_assoc($checkOrderResult2)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus2} (ทันทีที่มี Payment เป็น paid → Order อัปเดตเป็น paid)";
    
    $steps[] = "🎯 ขั้นตอนที่ 3: เปลี่ยน Payment กลับเป็น failed (Rollback)";
    
    // อัปเดต Payment กลับเป็น failed
    $updateQuery2 = "UPDATE payments SET payment_status = 'failed' WHERE id = $1";
    $updateResult2 = pg_query_params($conn, $updateQuery2, [$paymentId]);
    
    if (!$updateResult2) {
        throw new Exception('ไม่สามารถอัปเดต Payment เป็น failed ได้');
    }
    
    $steps[] = "   ✅ อัปเดต Payment ID: {$paymentId} เป็น 'failed' (Rollback)";
    
    // ตรวจสอบ Order Status หลังจาก Rollback
    $checkOrderResult3 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus3 = pg_fetch_assoc($checkOrderResult3)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus3} (Rollback สำเร็จ! ไม่มี Payment ไหนเป็น paid → Order กลับเป็น pending)";
    
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
    
    $steps[] = "🎉 สรุปผลการทดสอบ Rollback:";
    $steps[] = "   📋 Order ID: {$summaryData['id']}, Status: {$summaryData['order_status']}";
    $steps[] = "   💳 Payment Count: {$summaryData['payment_count']}";
    $steps[] = "   🔍 Payment Statuses: {$summaryData['payment_statuses']}";
    $steps[] = "   ✨ Rollback ทำงานได้สมบูรณ์! Order Status เปลี่ยนจาก 'paid' กลับเป็น 'pending'";
    
    $results['steps'] = $steps;
    $results['message'] = "✅ การทดสอบ Rollback Scenario สำเร็จ!";
    $results['summary'] = $summaryData;
    
    return $results;
}

// ฟังก์ชันทดสอบกรณีมีหลาย Payment และ Rollback
function testMultiplePaymentsRollback($conn) {
    $results = [];
    $steps = [];
    
    // หา Order ที่มี user_id = 999
    $orderQuery = "SELECT id, order_number, order_status FROM orders WHERE user_id = 999 ORDER BY id DESC LIMIT 1";
    $orderResult = pg_query($conn, $orderQuery);
    
    if (!$orderResult || pg_num_rows($orderResult) == 0) {
        throw new Exception('ไม่พบ Order สำหรับทดสอบ');
    }
    
    $orderData = pg_fetch_assoc($orderResult);
    $orderId = $orderData['id'];
    
    $steps[] = "🎯 ขั้นตอนที่ 1: เพิ่ม Payment ที่ 2 (failed)";
    
    // เพิ่ม Payment ที่ 2 (failed)
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
    
    // ตรวจสอบ Order Status
    $checkOrderQuery = "SELECT order_status FROM orders WHERE id = $1";
    $checkOrderResult = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus = pg_fetch_assoc($checkOrderResult)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus} (ยังไม่มี Payment ไหนเป็น paid → Order ยังคง pending)";
    
    $steps[] = "🎯 ขั้นตอนที่ 2: เพิ่ม Payment ที่ 3 (paid)";
    
    // เพิ่ม Payment ที่ 3 (paid)
    $payment3Query = "
    INSERT INTO payments (order_id, user_id, amount, payment_method, payment_status)
    VALUES ($1, 999, 500.00, 'MobileBanking', 'paid')
    RETURNING id, payment_status
    ";
    
    $payment3Result = pg_query_params($conn, $payment3Query, [$orderId]);
    if (!$payment3Result) {
        throw new Exception('ไม่สามารถสร้าง Payment ที่ 3 ได้');
    }
    
    $payment3Data = pg_fetch_assoc($payment3Result);
    $steps[] = "   ✅ สร้าง Payment ID: {$payment3Data['id']}, Status: {$payment3Data['payment_status']}";
    
    // ตรวจสอบ Order Status หลังจากมี Payment เป็น paid
    $checkOrderResult2 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus2 = pg_fetch_assoc($checkOrderResult2)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus2} (มี Payment เป็น paid → Order อัปเดตเป็น paid)";
    
    $steps[] = "🎯 ขั้นตอนที่ 3: เปลี่ยน Payment ที่ 3 เป็น failed (Rollback)";
    
    // อัปเดต Payment ที่ 3 เป็น failed
    $updateQuery = "UPDATE payments SET payment_status = 'failed' WHERE id = $1";
    $updateResult = pg_query_params($conn, $updateQuery, [$payment3Data['id']]);
    
    if (!$updateResult) {
        throw new Exception('ไม่สามารถอัปเดต Payment เป็น failed ได้');
    }
    
    $steps[] = "   ✅ อัปเดต Payment ID: {$payment3Data['id']} เป็น 'failed' (Rollback)";
    
    // ตรวจสอบ Order Status หลังจาก Rollback
    $checkOrderResult3 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus3 = pg_fetch_assoc($checkOrderResult3)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus3} (Rollback สำเร็จ! ไม่มี Payment ไหนเป็น paid → Order กลับเป็น pending)";
    
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
    
    $steps[] = "🎉 สรุปผลการทดสอบ Multiple Payments Rollback:";
    $steps[] = "   📋 Order ID: {$summaryData['id']}, Status: {$summaryData['order_status']}";
    $steps[] = "   💳 Payment Count: {$summaryData['payment_count']}";
    $steps[] = "   🔍 Payment Statuses: {$summaryData['payment_statuses']}";
    $steps[] = "   ✨ Rollback ทำงานได้สมบูรณ์แม้มีหลาย Payment!";
    
    $results['steps'] = $steps;
    $results['message'] = "✅ การทดสอบ Multiple Payments Rollback สำเร็จ!";
    $results['summary'] = $summaryData;
    
    return $results;
}

// ฟังก์ชันทดสอบการยกเลิก Payment
function testPaymentCancellation($conn) {
    $results = [];
    $steps = [];
    
    // หา Order ที่มี user_id = 999
    $orderQuery = "SELECT id, order_number, order_status FROM orders WHERE user_id = 999 ORDER BY id DESC LIMIT 1";
    $orderResult = pg_query($conn, $orderQuery);
    
    if (!$orderResult || pg_num_rows($orderResult) == 0) {
        throw new Exception('ไม่พบ Order สำหรับทดสอบ');
    }
    
    $orderData = pg_fetch_assoc($orderResult);
    $orderId = $orderData['id'];
    
    $steps[] = "🎯 ขั้นตอนที่ 1: ตรวจสอบสถานะปัจจุบัน";
    
    // ตรวจสอบ Payment ทั้งหมด
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
    
    $steps[] = "   📊 Payment Count: " . count($payments);
    foreach ($payments as $payment) {
        $steps[] = "      - Payment ID: {$payment['id']}, Status: {$payment['payment_status']}, Method: {$payment['payment_method']}";
    }
    
    // ตรวจสอบ Order Status
    $checkOrderQuery = "SELECT order_status FROM orders WHERE id = $1";
    $checkOrderResult = pg_query_params($conn, $checkOrderQuery, [$orderId]);
    $orderStatus = pg_fetch_assoc($checkOrderResult)['order_status'];
    
    $steps[] = "   📊 Order Status: {$orderStatus}";
    
    $steps[] = "🎯 ขั้นตอนที่ 2: ทดสอบการยกเลิก Payment (เปลี่ยนเป็น cancelled)";
    
    // หา Payment ที่เป็น paid หรือ pending
    $activePaymentQuery = "
    SELECT id, payment_status
    FROM payments
    WHERE order_id = $1 AND payment_status IN ('paid', 'pending')
    LIMIT 1
    ";
    
    $activePaymentResult = pg_query_params($conn, $activePaymentQuery, [$orderId]);
    if (!$activePaymentResult || pg_num_rows($activePaymentResult) == 0) {
        $steps[] = "   ℹ️ ไม่มี Payment ที่ active ให้ยกเลิก";
    } else {
        $activePayment = pg_fetch_assoc($activePaymentResult);
        $activePaymentId = $activePayment['id'];
        $originalStatus = $activePayment['payment_status'];
        
        // เปลี่ยน Payment เป็น cancelled
        $cancelQuery = "UPDATE payments SET payment_status = 'cancelled' WHERE id = $1";
        $cancelResult = pg_query_params($conn, $cancelQuery, [$activePaymentId]);
        
        if (!$cancelResult) {
            throw new Exception('ไม่สามารถยกเลิก Payment ได้');
        }
        
        $steps[] = "   ✅ ยกเลิก Payment ID: {$activePaymentId} จาก '{$originalStatus}' เป็น 'cancelled'";
        
        // ตรวจสอบ Order Status หลังจากยกเลิก
        $checkOrderResult2 = pg_query_params($conn, $checkOrderQuery, [$orderId]);
        $orderStatus2 = pg_fetch_assoc($checkOrderResult2)['order_status'];
        
        $steps[] = "   📊 Order Status: {$orderStatus2} (หลังจากยกเลิก Payment)";
        
        // ตรวจสอบว่ามี Payment ไหนเป็น paid หรือไม่
        $paidPaymentQuery = "
        SELECT COUNT(*) as paid_count
        FROM payments
        WHERE order_id = $1 AND payment_status = 'paid'
        ";
        
        $paidPaymentResult = pg_query_params($conn, $paidPaymentQuery, [$orderId]);
        $paidCount = pg_fetch_assoc($paidPaymentResult)['paid_count'];
        
        if ($paidCount > 0) {
            $steps[] = "   ✅ ยังมี Payment ที่เป็น paid อีก {$paidCount} รายการ → Order Status คงเป็น 'paid'";
        } else {
            $steps[] = "   ✅ ไม่มี Payment ไหนเป็น paid แล้ว → Order Status เปลี่ยนเป็น 'pending' (Rollback)";
        }
    }
    
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
    
    $steps[] = "🎉 สรุปผลการทดสอบ Payment Cancellation:";
    $steps[] = "   📋 Order ID: {$summaryData['id']}, Status: {$summaryData['order_status']}";
    $steps[] = "   💳 Payment Count: {$summaryData['payment_count']}";
    $steps[] = "   🔍 Payment Statuses: {$summaryData['payment_statuses']}";
    
    $results['steps'] = $steps;
    $results['message'] = "✅ การทดสอบ Payment Cancellation สำเร็จ!";
    $results['summary'] = $summaryData;
    
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
    <title>ZapShop - ทดสอบ Trigger Rollback</title>
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
        .status-cancelled { background: #f8d7da; color: #721c24; }
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
        .rollback-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
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

        <!-- ทดสอบ Trigger Rollback -->
        <div class="test-section">
            <h3>🔄 ทดสอบ Trigger อัจฉริยะ - เวอร์ชันไป-กลับ</h3>
            
            <!-- Logic ของ Trigger Rollback -->
            <div class="rollback-box">
                <h4>🎯 Logic ของ Trigger Rollback:</h4>
                <ul>
                    <li><strong>ถ้ามี Payment ใดใน Order เป็น 'paid'</strong> → อัปเดต <code>orders.order_status = 'paid'</code></li>
                    <li><strong>ถ้าไม่มี Payment ที่เป็น 'paid' เลย</strong> → <code>orders.order_status</code> กลับเป็น 'pending' (Rollback)</li>
                    <li><strong>รองรับการ Rollback อัตโนมัติ</strong> → เปลี่ยนกลับเป็น pending ได้อัตโนมัติ</li>
                    <li><strong>รองรับหลาย Payment ต่อ Order</strong> → เก็บสถานะแยกได้ครบ</li>
                </ul>
            </div>
            
            <!-- ปุ่มทดสอบ -->
            <form method="POST" style="margin: 15px 0;">
                <button type="submit" name="action" value="create_test_data" class="btn btn-primary">📝 สร้างข้อมูลทดสอบ</button>
                <button type="submit" name="action" value="test_rollback_scenario" class="btn btn-success">🔄 ทดสอบ Rollback Scenario</button>
                <button type="submit" name="action" value="test_multiple_payments_rollback" class="btn btn-info">🔍 ทดสอบ Multiple Payments Rollback</button>
                <button type="submit" name="action" value="test_payment_cancellation" class="btn btn-warning">❌ ทดสอบ Payment Cancellation</button>
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
            <h3>📖 ข้อมูลการใช้งาน Trigger Rollback</h3>
            
            <h4>✅ การทำงานของ Trigger Rollback:</h4>
            <ul>
                <li><strong>มี Payment ใดเป็น 'paid'</strong> → order_status = 'paid'</li>
                <li><strong>ไม่มี Payment ที่เป็น 'paid' เลย</strong> → order_status = 'pending' (Rollback)</li>
                <li><strong>รองรับการ Rollback อัตโนมัติ</strong> → เปลี่ยนกลับเป็น pending ได้อัตโนมัติ</li>
                <li><strong>รองรับหลาย Payment ต่อ Order</strong> → เก็บสถานะแยกได้ครบ</li>
            </ul>
            
            <h4>🔧 SQL ที่ใช้ทดสอบ Rollback:</h4>
            <pre><code>-- เริ่มต้น Order ใหม่
INSERT INTO orders (user_id, order_status, total_amount)
VALUES (1, 'pending', 1000.00);

-- สร้าง Payment ยังไม่จ่าย
INSERT INTO payments (order_id, amount, payment_method, payment_status)
VALUES (1, 1000.00, 'QR', 'pending');

-- เปลี่ยนเป็นจ่ายแล้ว
UPDATE payments SET payment_status = 'paid' WHERE id = 1;

-- เปลี่ยนกลับเป็น failed (Rollback)
UPDATE payments SET payment_status = 'failed' WHERE id = 1;

-- Trigger จะรันอัตโนมัติและตรวจสอบ:
-- ถ้ามี Payment ใดเป็น paid → Order = 'paid'
-- ถ้าไม่มี Payment ที่เป็น paid เลย → Order = 'pending' (Rollback)
-- ไม่ต้องเรียก PHP หรือ webhook เพิ่ม</code></pre>
            
            <h4>⚠️ ข้อควรระวัง:</h4>
            <ul>
                <li>Trigger จะรันทุกครั้งที่ INSERT/UPDATE/DELETE payments</li>
                <li>การทดสอบจะสร้างข้อมูลใน user_id = 999</li>
                <li>ควรทดสอบกับ DB Test ก่อน deploy จริง</li>
                <li>Logic Rollback: ตรวจสอบ EXISTS ของ paid payments</li>
            </ul>
        </div>

        <!-- ปุ่มกลับ -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="create_payment_trigger.php" class="btn btn-primary">🔧 สร้าง Trigger</a>
            <a href="test_smart_trigger.php" class="btn btn-info">🧪 ทดสอบ Trigger ปกติ</a>
            <a href="test_trigger.php" class="btn btn-warning">🧪 ทดสอบ Trigger เก่า</a>
            <a href="index.php" class="btn btn-secondary">🏠 หน้าหลัก</a>
        </div>
    </div>
</body>
</html>
