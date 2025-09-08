<?php
/**
 * Test Simple Order Creation
 * ทดสอบการสร้างคำสั่งซื้อแบบง่าย
 */

session_start();
require_once 'config.php';
require_once 'payment_handler.php';

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 ทดสอบการสร้างคำสั่งซื้อแบบง่าย</h2>";

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    echo "<div style='color: red;'>❌ กรุณาเข้าสู่ระบบก่อน</div>";
    echo "<a href='user-login.php'>เข้าสู่ระบบ</a>";
    exit;
}

echo "<h3>1. ข้อมูลผู้ใช้</h3>";
echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";

// สร้างข้อมูลทดสอบ
$testProducts = [
    [
        'id' => 1,
        'name' => 'สินค้าทดสอบ 1',
        'price' => 500.00,
        'quantity' => 2
    ],
    [
        'id' => 2,
        'name' => 'สินค้าทดสอบ 2',
        'price' => 300.00,
        'quantity' => 1
    ]
];

$testShippingInfo = [
    'address' => '123 ถนนทดสอบ, แขวงทดสอบ, เขตทดสอบ, กรุงเทพฯ 10000',
    'tel' => '0812345678',
    'email' => 'test@example.com'
];

$totalAmount = array_sum(array_map(function($p) {
    return $p['price'] * $p['quantity'];
}, $testProducts));

echo "<h3>2. ข้อมูลทดสอบ</h3>";
echo "📝 จำนวนสินค้า: " . count($testProducts) . " รายการ<br>";
echo "📝 ยอดรวม: ฿" . number_format($totalAmount, 2) . "<br>";
echo "📝 ที่อยู่จัดส่ง: " . $testShippingInfo['address'] . "<br>";
echo "📝 เบอร์โทร: " . $testShippingInfo['tel'] . "<br>";
echo "📝 อีเมล: " . $testShippingInfo['email'] . "<br>";

echo "<h3>3. ทดสอบการสร้างคำสั่งซื้อ</h3>";

try {
    // ทดสอบสร้างคำสั่งซื้อ
    echo "🔄 กำลังสร้างคำสั่งซื้อ...<br>";
    
    $orderResult = createOrder($_SESSION['user_id'], $testProducts, $totalAmount, $testShippingInfo);
    
    if ($orderResult['success']) {
        echo "✅ สร้างคำสั่งซื้อสำเร็จ!<br>";
        echo "   - Order ID: {$orderResult['order_id']}<br>";
        echo "   - Order Number: {$orderResult['order_number']}<br>";
        echo "   - ข้อความ: {$orderResult['message']}<br>";
        
        echo "<h3>4. ทดสอบการชำระเงิน QR Code</h3>";
        
        // ทดสอบการชำระเงิน QR Code
        echo "🔄 กำลังสร้างการชำระเงิน QR Code...<br>";
        
        $qrResult = handleQRCodePayment($orderResult['order_id'], $_SESSION['user_id'], $totalAmount);
        
        if ($qrResult['success']) {
            echo "✅ สร้างการชำระเงิน QR Code สำเร็จ!<br>";
            echo "   - Payment ID: {$qrResult['payment_id']}<br>";
            echo "   - PromptPay ID: {$qrResult['promptpay_id']}<br>";
            echo "   - ข้อความ: {$qrResult['message']}<br>";
            
            echo "<h3>5. ทดสอบการสร้าง QR Code</h3>";
            
            // ทดสอบการสร้าง QR Code
            echo "🔄 กำลังสร้าง QR Code...<br>";
            
            $qrData = [
                'amount' => $totalAmount,
                'order_id' => $orderResult['order_id']
            ];
            
            // เรียก API สร้าง QR Code
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/generate_qr_promptpay.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($qrData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                echo "❌ Error ในการเรียก API: $curlError<br>";
            } else {
                echo "✅ HTTP Status: $httpCode<br>";
                echo "📝 Response: <pre>" . htmlspecialchars($response) . "</pre><br>";
                
                $qrResponse = json_decode($response, true);
                if ($qrResponse && isset($qrResponse['success']) && $qrResponse['success']) {
                    echo "✅ สร้าง QR Code สำเร็จ!<br>";
                    echo "   - Payload: {$qrResponse['data']['payload']}<br>";
                    // ระบบ QR Code ถูกลบออกแล้ว
                    echo "<div style='text-align: center; margin: 20px;'>";
                    echo "<h4>ระบบ QR Code ถูกลบออกแล้ว</h4>";
                    echo "<p>กรุณาใช้วิธีการชำระเงินอื่น</p>";
                    echo "<br><small>จำนวนเงิน: ฿" . number_format($totalAmount, 2) . "</small>";
                    echo "</div>";
                    
                } else {
                    echo "❌ สร้าง QR Code ล้มเหลว<br>";
                    if (isset($qrResponse['error'])) {
                        echo "   - Error: {$qrResponse['error']}<br>";
                    }
                }
            }
            
        } else {
            echo "❌ สร้างการชำระเงิน QR Code ล้มเหลว: {$qrResult['message']}<br>";
        }
        
    } else {
        echo "❌ สร้างคำสั่งซื้อล้มเหลว: {$orderResult['message']}<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>📋 สรุปการทดสอบ</h3>";
echo "หากการทดสอบสำเร็จ ระบบการชำระเงินควรทำงานได้ปกติ<br>";
echo "หากพบปัญหา ให้ตรวจสอบ:<br>";
echo "1. การเชื่อมต่อฐานข้อมูล<br>";
echo "2. การสร้างตารางที่จำเป็น<br>";
echo "3. การสร้างคำสั่งซื้อ<br>";
echo "4. การสร้างการชำระเงิน<br>";
echo "5. การสร้าง QR Code<br>";

echo "<br><a href='debug_payment.php' class='btn btn-primary'>ตรวจสอบระบบ</a>";
echo "<a href='checkout.php' class='btn btn-secondary'>กลับไปหน้าชำระเงิน</a>";
echo "<a href='test_qr_promptpay.php' class='btn btn-success'>ทดสอบ QR Code</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; margin-top: 20px; }
.btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }
hr { border: 1px solid #ddd; margin: 20px 0; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
