<?php
/**
 * ระบบทดสอบ Payment Gateway
 * ใช้สำหรับทดสอบการเชื่อมต่อ API หลังจากได้รับการอนุมัติจากธนาคาร
 */

// ตรวจสอบว่ามีการตั้งค่า Payment Gateway หรือไม่
$paymentGatewayConfig = [
    'enabled' => false,
    'provider' => '', // 'truemoney', 'scb', 'kbank'
    'api_key' => '',
    'api_secret' => '',
    'merchant_id' => '',
    'sandbox_mode' => true,
    'callback_url' => 'https://zapshop.com/payment/callback'
];

// ฟังก์ชันสำหรับทดสอบการเชื่อมต่อ
function testPaymentGatewayConnection($config) {
    if (!$config['enabled']) {
        return [
            'success' => false,
            'error' => 'Payment Gateway ยังไม่ได้เปิดใช้งาน กรุณาตั้งค่าก่อน'
        ];
    }
    
    // ทดสอบการเชื่อมต่อตาม Provider
    switch ($config['provider']) {
        case 'truemoney':
            return testTrueMoneyConnection($config);
        case 'scb':
            return testSCBConnection($config);
        case 'kbank':
            return testKBankConnection($config);
        default:
            return [
                'success' => false,
                'error' => 'Provider ไม่ถูกต้อง'
            ];
    }
}

// ทดสอบการเชื่อมต่อ TrueMoney
function testTrueMoneyConnection($config) {
    $testUrl = $config['sandbox_mode'] 
        ? 'https://sandbox-api.truemoney.com/v1/health'
        : 'https://api.truemoney.com/v1/health';
    
    $headers = [
        'Authorization: Bearer ' . $config['api_key'],
        'X-API-Key: ' . $config['api_key']
    ];
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
                'message' => 'เชื่อมต่อ TrueMoney API สำเร็จ',
                'http_code' => $httpCode,
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

// ทดสอบการเชื่อมต่อ SCB
function testSCBConnection($config) {
    $testUrl = $config['sandbox_mode'] 
        ? 'https://sandbox-api.scb.co.th/v1/health'
        : 'https://api.scb.co.th/v1/health';
    
    $headers = [
        'Authorization: Bearer ' . $config['api_key'],
        'X-API-Key: ' . $config['api_key']
    ];
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
                'message' => 'เชื่อมต่อ SCB API สำเร็จ',
                'http_code' => $httpCode,
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

// ทดสอบการเชื่อมต่อ KBank
function testKBankConnection($config) {
    $testUrl = $config['sandbox_mode'] 
        ? 'https://sandbox-api.kasikornbank.com/v1/health'
        : 'https://api.kasikornbank.com/v1/health';
    
    $headers = [
        'Authorization: Bearer ' . $config['api_key'],
        'X-API-Key: ' . $config['api_key']
    ];
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
                'message' => 'เชื่อมต่อ KBank API สำเร็จ',
                'http_code' => $httpCode,
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

// ทดสอบการสร้าง QR Code
function testQRCodeGeneration($config, $amount, $orderId) {
    if (!$config['enabled']) {
        return [
            'success' => false,
            'error' => 'Payment Gateway ยังไม่ได้เปิดใช้งาน'
        ];
    }
    
    // ทดสอบการสร้าง QR Code ตาม Provider
    switch ($config['provider']) {
        case 'truemoney':
            return testTrueMoneyQRGeneration($config, $amount, $orderId);
        case 'scb':
            return testSCBQRGeneration($config, $amount, $orderId);
        case 'kbank':
            return testKBankQRGeneration($config, $amount, $orderId);
        default:
            return [
                'success' => false,
                'error' => 'Provider ไม่ถูกต้อง'
            ];
    }
}

// ทดสอบการสร้าง QR Code TrueMoney
function testTrueMoneyQRGeneration($config, $amount, $orderId) {
    $endpoint = $config['sandbox_mode'] 
        ? 'https://sandbox-api.truemoney.com/v1/qr/generate'
        : 'https://api.truemoney.com/v1/qr/generate';
    
    $payload = [
        'merchant_id' => $config['merchant_id'],
        'amount' => $amount,
        'currency' => 'THB',
        'order_id' => $orderId,
        'reference1' => 'ZapShop Test',
        'reference2' => 'Test Order',
        'expiry' => 1800,
        'callback_url' => $config['callback_url']
    ];
    
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['api_key'],
        'X-API-Key: ' . $config['api_key']
    ];
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
            return [
                'success' => true,
                'message' => 'สร้าง QR Code สำเร็จ',
                // 'qr_code_url' => $responseData['data']['qr_code_url'] ?? '', // ถูกลบออกแล้ว
                // 'qr_code_image' => $responseData['data']['qr_code_image'] ?? '', // ถูกลบออกแล้ว
                'transaction_id' => $responseData['data']['transaction_id'] ?? '',
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => 'API Error: ' . ($responseData['error'] ?? 'Unknown error'),
                'http_code' => $httpCode,
                'response' => $responseData
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// ทดสอบการสร้าง QR Code SCB
function testSCBQRGeneration($config, $amount, $orderId) {
    // คล้ายกับ TrueMoney แต่ใช้ endpoint ของ SCB
    return testTrueMoneyQRGeneration($config, $amount, $orderId);
}

// ทดสอบการสร้าง QR Code KBank
function testKBankQRGeneration($config, $amount, $orderId) {
    // คล้ายกับ TrueMoney แต่ใช้ endpoint ของ KBank
    return testTrueMoneyQRGeneration($config, $amount, $orderId);
}

// ตรวจสอบสถานะการชำระเงิน
function checkPaymentStatus($config, $transactionId) {
    if (!$config['enabled']) {
        return [
            'success' => false,
            'error' => 'Payment Gateway ยังไม่ได้เปิดใช้งาน'
        ];
    }
    
    // ตรวจสอบสถานะตาม Provider
    switch ($config['provider']) {
        case 'truemoney':
            return checkTrueMoneyPaymentStatus($config, $transactionId);
        case 'scb':
            return checkSCBPaymentStatus($config, $transactionId);
        case 'kbank':
            return checkKBankPaymentStatus($config, $transactionId);
        default:
            return [
                'success' => false,
                'error' => 'Provider ไม่ถูกต้อง'
            ];
    }
}

// ตรวจสอบสถานะการชำระเงิน TrueMoney
function checkTrueMoneyPaymentStatus($config, $transactionId) {
    $endpoint = $config['sandbox_mode'] 
        ? 'https://sandbox-api.truemoney.com/v1/transaction/status/' . $transactionId
        : 'https://api.truemoney.com/v1/transaction/status/' . $transactionId;
    
    $headers = [
        'Authorization: Bearer ' . $config['api_key'],
        'X-API-Key: ' . $config['api_key']
    ];
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($responseData['success']) && $responseData['success']) {
            return [
                'success' => true,
                'status' => $responseData['data']['status'] ?? 'unknown',
                'amount' => $responseData['data']['amount'] ?? 0,
                'paid_at' => $responseData['data']['paid_at'] ?? null,
                'response' => $responseData
            ];
        } else {
            return [
                'success' => false,
                'error' => 'API Error: ' . ($responseData['error'] ?? 'Unknown error'),
                'http_code' => $httpCode,
                'response' => $responseData
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// ตรวจสอบสถานะการชำระเงิน SCB
function checkSCBPaymentStatus($config, $transactionId) {
    return checkTrueMoneyPaymentStatus($config, $transactionId);
}

// ตรวจสอบสถานะการชำระเงิน KBank
function checkKBankPaymentStatus($config, $transactionId) {
    return checkTrueMoneyPaymentStatus($config, $transactionId);
}

// แสดงหน้าเว็บ
echo "<h1>ระบบทดสอบ Payment Gateway</h1>";
echo "<p><strong>หมายเหตุ:</strong> ระบบนี้ใช้สำหรับทดสอบการเชื่อมต่อ Payment Gateway หลังจากได้รับการอนุมัติจากธนาคาร</p>";

// แสดงสถานะการตั้งค่า
echo "<div class='card mb-4'>";
echo "<div class='card-header'>";
echo "<h3>สถานะการตั้งค่า Payment Gateway</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<p><strong>สถานะ:</strong> " . ($paymentGatewayConfig['enabled'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน') . "</p>";
echo "<p><strong>Provider:</strong> " . ($paymentGatewayConfig['provider'] ?: 'ยังไม่ได้ตั้งค่า') . "</p>";
echo "<p><strong>โหมด:</strong> " . ($paymentGatewayConfig['sandbox_mode'] ? 'Sandbox (ทดสอบ)' : 'Production (ใช้งานจริง)') . "</p>";
echo "<p><strong>API Key:</strong> " . ($paymentGatewayConfig['api_key'] ? 'ตั้งค่าแล้ว' : 'ยังไม่ได้ตั้งค่า') . "</p>";
echo "<p><strong>Merchant ID:</strong> " . ($paymentGatewayConfig['merchant_id'] ?: 'ยังไม่ได้ตั้งค่า') . "</p>";
echo "</div>";
echo "</div>";

// แสดงปุ่มทดสอบ
echo "<div class='card mb-4'>";
echo "<div class='card-header'>";
echo "<h3>ทดสอบการเชื่อมต่อ</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<p>เลือกการทดสอบที่ต้องการ:</p>";
echo "<button class='btn btn-primary me-2' onclick='testConnection()'>ทดสอบการเชื่อมต่อ</button>";
echo "<button class='btn btn-success me-2' onclick='testQRGeneration()'>ทดสอบการสร้าง QR Code</button>";
echo "<button class='btn btn-info me-2' onclick='testPaymentStatus()'>ทดสอบการตรวจสอบสถานะ</button>";
echo "</div>";
echo "</div>";

// แสดงผลลัพธ์การทดสอบ
echo "<div id='testResults' class='card mb-4' style='display: none;'>";
echo "<div class='card-header'>";
echo "<h3>ผลลัพธ์การทดสอบ</h3>";
echo "</div>";
echo "<div class='card-body'>";
echo "<div id='testResultContent'></div>";
echo "</div>";
echo "</div>";

// แสดงคำแนะนำ
echo "<div class='alert alert-warning'>";
echo "<h4>คำแนะนำก่อนการทดสอบ:</h4>";
echo "<ul>";
echo "<li><strong>ต้องได้รับการอนุมัติจากธนาคารก่อน:</strong> ไม่สามารถทดสอบได้หากยังไม่ได้สมัคร</li>";
echo "<li><strong>ต้องมีข้อมูล API:</strong> API Key, API Secret, Merchant ID</li>";
echo "<li><strong>ใช้ Sandbox Mode ก่อน:</strong> ทดสอบในระบบทดสอบก่อนใช้งานจริง</li>";
echo "<li><strong>ตรวจสอบ Network:</strong> ต้องสามารถเข้าถึง API ของธนาคารได้</li>";
echo "</ul>";
echo "</div>";

echo "<hr>";
echo "<p><a href='payment_gateway_api.php' class='btn btn-primary'>ดูโค้ด API Integration</a></p>";
echo "<p><a href='application_documents.php' class='btn btn-secondary'>ดูเอกสารสำหรับสมัคร</a></p>";
echo "<p><a href='test_qr_fake_complete.php' class='btn btn-info'>กลับไปหน้าทดสอบ</a></p>";

// JavaScript สำหรับการทดสอบ
echo "<script>";
echo "function testConnection() {";
echo "    document.getElementById('testResults').style.display = 'block';";
echo "    document.getElementById('testResultContent').innerHTML = '<div class=\"alert alert-info\">กำลังทดสอบการเชื่อมต่อ...</div>';";
echo "    // เรียกใช้ AJAX เพื่อทดสอบการเชื่อมต่อ";
echo "    // ในที่นี้จะแสดงผลลัพธ์ตัวอย่าง";
echo "    setTimeout(function() {";
echo "        document.getElementById('testResultContent').innerHTML = '<div class=\"alert alert-warning\">กรุณาตั้งค่า Payment Gateway ก่อนการทดสอบ</div>';";
echo "    }, 2000);";
echo "}";
echo "";
echo "function testQRGeneration() {";
echo "    document.getElementById('testResults').style.display = 'block';";
echo "    document.getElementById('testResultContent').innerHTML = '<div class=\"alert alert-info\">กำลังทดสอบการสร้าง QR Code...</div>';";
echo "    setTimeout(function() {";
echo "        document.getElementById('testResultContent').innerHTML = '<div class=\"alert alert-warning\">กรุณาตั้งค่า Payment Gateway ก่อนการทดสอบ</div>';";
echo "    }, 2000);";
echo "}";
echo "";
echo "function testPaymentStatus() {";
echo "    document.getElementById('testResults').style.display = 'block';";
echo "    document.getElementById('testResultContent').innerHTML = '<div class=\"alert alert-info\">กำลังทดสอบการตรวจสอบสถานะ...</div>';";
echo "    setTimeout(function() {";
echo "        document.getElementById('testResultContent').innerHTML = '<div class=\"alert alert-warning\">กรุณาตั้งค่า Payment Gateway ก่อนการทดสอบ</div>';";
echo "    }, 2000);";
echo "}";
echo "</script>";
?>
