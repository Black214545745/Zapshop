<?php
/**
 * Debug Payment System
 * ตรวจสอบปัญหาการสร้างคำสั่งซื้อและการชำระเงิน
 */

session_start();
require_once 'config.php';
require_once 'payment_handler.php';

// เปิด error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debug Payment System</h2>";

// 1. ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h3>1. ตรวจสอบการเชื่อมต่อฐานข้อมูล</h3>";
try {
    $conn = getConnection();
    if ($conn) {
        echo "✅ การเชื่อมต่อฐานข้อมูลสำเร็จ<br>";
        
        // ตรวจสอบตารางที่จำเป็น
        $tables = ['payments', 'orders', 'order_items', 'products'];
        foreach ($tables as $table) {
            $checkTable = "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = '$table')";
            $result = pg_query($conn, $checkTable);
            if ($result) {
                $exists = pg_fetch_result($result, 0, 0);
                echo $exists === 't' ? "✅ ตาราง $table มีอยู่<br>" : "❌ ตาราง $table ไม่มี<br>";
            }
        }
        
        pg_close($conn);
    } else {
        echo "❌ ไม่สามารถเชื่อมต่อฐานข้อมูลได้<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 2. ตรวจสอบ Session
echo "<h3>2. ตรวจสอบ Session</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ ไม่มี User ID ใน Session<br>";
}

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "✅ มีสินค้าในตะกร้า: " . count($_SESSION['cart']) . " รายการ<br>";
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        echo "   - Product ID: $productId, Quantity: $quantity<br>";
    }
} else {
    echo "❌ ไม่มีสินค้าในตะกร้า<br>";
}

// 3. ตรวจสอบข้อมูลสินค้า
echo "<h3>3. ตรวจสอบข้อมูลสินค้า</h3>";
if (!empty($_SESSION['cart'])) {
    try {
        $conn = getConnection();
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $productQuery = "SELECT id, name, price FROM products WHERE id = $1";
            $productResult = pg_query_params($conn, $productQuery, [$productId]);
            
            if ($productResult && pg_num_rows($productResult) > 0) {
                $product = pg_fetch_assoc($productResult);
                echo "✅ Product ID: {$product['id']}, Name: {$product['name']}, Price: ฿{$product['price']}<br>";
            } else {
                echo "❌ ไม่พบสินค้า ID: $productId<br>";
            }
        }
        pg_close($conn);
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

// 4. ทดสอบการสร้างคำสั่งซื้อ
echo "<h3>4. ทดสอบการสร้างคำสั่งซื้อ</h3>";
if (!empty($_SESSION['cart']) && isset($_SESSION['user_id'])) {
    try {
        // สร้างข้อมูลทดสอบ
        $testProducts = [];
        $conn = getConnection();
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $productQuery = "SELECT id, name, price FROM products WHERE id = $1";
            $productResult = pg_query_params($conn, $productQuery, [$productId]);
            
            if ($productResult && pg_num_rows($productResult) > 0) {
                $product = pg_fetch_assoc($productResult);
                $testProducts[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => floatval($product['price']),
                    'quantity' => $quantity
                ];
            }
        }
        pg_close($conn);
        
        if (!empty($testProducts)) {
            $testShippingInfo = [
                'address' => 'ที่อยู่ทดสอบ',
                'tel' => '0812345678',
                'email' => 'test@example.com'
            ];
            
            $totalAmount = array_sum(array_map(function($p) {
                return $p['price'] * $p['quantity'];
            }, $testProducts));
            
            echo "📝 ทดสอบสร้างคำสั่งซื้อ:<br>";
            echo "   - จำนวนสินค้า: " . count($testProducts) . " รายการ<br>";
            echo "   - ยอดรวม: ฿" . number_format($totalAmount, 2) . "<br>";
            
            // ทดสอบสร้างคำสั่งซื้อ
            $orderResult = createOrder($_SESSION['user_id'], $testProducts, $totalAmount, $testShippingInfo);
            
            if ($orderResult['success']) {
                echo "✅ สร้างคำสั่งซื้อสำเร็จ!<br>";
                echo "   - Order ID: {$orderResult['order_id']}<br>";
                echo "   - Order Number: {$orderResult['order_number']}<br>";
                
                // ทดสอบการชำระเงิน QR Code
                echo "<h3>5. ทดสอบการชำระเงิน QR Code</h3>";
                $qrResult = handleQRCodePayment($orderResult['order_id'], $_SESSION['user_id'], $totalAmount);
                
                if ($qrResult['success']) {
                    echo "✅ สร้างการชำระเงิน QR Code สำเร็จ!<br>";
                    echo "   - Payment ID: {$qrResult['payment_id']}<br>";
                    echo "   - PromptPay ID: {$qrResult['promptpay_id']}<br>";
                } else {
                    echo "❌ สร้างการชำระเงิน QR Code ล้มเหลว: {$qrResult['message']}<br>";
                }
                
            } else {
                echo "❌ สร้างคำสั่งซื้อล้มเหลว: {$orderResult['message']}<br>";
            }
        } else {
            echo "❌ ไม่มีข้อมูลสินค้าที่ถูกต้องสำหรับทดสอบ<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error ในการทดสอบ: " . $e->getMessage() . "<br>";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
    }
}

// 5. ตรวจสอบ PromptPay Config
echo "<h3>6. ตรวจสอบ PromptPay Config</h3>";
try {
    $promptpayId = getPromptPayId();
    echo "✅ PromptPay ID: $promptpayId<br>";
    
    if (validatePromptPayId($promptpayId)) {
        echo "✅ PromptPay ID ถูกต้อง<br>";
    } else {
        echo "❌ PromptPay ID ไม่ถูกต้อง<br>";
    }
    
    $shopInfo = getShopInfo();
    echo "✅ ข้อมูลร้านค้า: " . $shopInfo['name'] . " (" . $shopInfo['email'] . ")<br>";
    
} catch (Exception $e) {
    echo "❌ Error ใน PromptPay Config: " . $e->getMessage() . "<br>";
}

// 6. ทดสอบการสร้าง QR Code
echo "<h3>7. ทดสอบการสร้าง QR Code</h3>";
try {
    $testAmount = 1000.00;
    $promptpayId = getPromptPayId();
    
    // สร้าง payload แบบง่าย
    $simplePayload = "00020101021129370016A00000067701011101130066" . substr($promptpayId, 1) . "5802TH53037645406" . number_format($testAmount, 2, '', '') . "6304";
    
    // คำนวณ CRC
    $crc = crc16($simplePayload);
    $simplePayload .= strtoupper(dechex($crc));
    
    echo "📝 Payload ทดสอบ: $simplePayload<br>";
    echo "📝 จำนวนเงิน: ฿$testAmount<br>";
    echo "📝 PromptPay ID: $promptpayId<br>";
    
    // ทดสอบสร้าง QR Code URL
    $qrUrl = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" . urlencode($simplePayload);
    echo "✅ QR Code URL: <a href='$qrUrl' target='_blank'>คลิกเพื่อดู QR Code</a><br>";
    
} catch (Exception $e) {
    echo "❌ Error ในการสร้าง QR Code: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>📋 สรุปการตรวจสอบ</h3>";
echo "หากพบปัญหา ให้ตรวจสอบ:<br>";
echo "1. การเชื่อมต่อฐานข้อมูล<br>";
echo "2. การสร้างตารางที่จำเป็น<br>";
echo "3. ข้อมูลสินค้าในตะกร้า<br>";
echo "4. การสร้างคำสั่งซื้อ<br>";
echo "5. การสร้างการชำระเงิน<br>";
echo "6. การสร้าง QR Code<br>";

echo "<br><a href='checkout.php' class='btn btn-primary'>กลับไปหน้าชำระเงิน</a>";
echo "<a href='test_qr_promptpay.php' class='btn btn-secondary'>ทดสอบ QR Code</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #2c3e50; }
h3 { color: #34495e; margin-top: 20px; }
.btn { display: inline-block; padding: 10px 20px; margin: 5px; text-decoration: none; border-radius: 5px; }
.btn-primary { background: #007bff; color: white; }
.btn-secondary { background: #6c757d; color: white; }
hr { border: 1px solid #ddd; margin: 20px 0; }
</style>
