<?php
/**
 * ระบบตั้งค่า Payment Gateway
 * ใช้สำหรับจัดการการตั้งค่า Payment Gateway และทดสอบการเชื่อมต่อ
 */

session_start();

// ตรวจสอบการตั้งค่า Payment Gateway
$configFile = 'payment_gateway_settings.json';
$defaultConfig = [
    'enabled' => false,
    'provider' => '',
    'api_key' => '',
    'api_secret' => '',
    'merchant_id' => '',
    'sandbox_mode' => true,
    'callback_url' => 'https://zapshop.com/payment/callback',
    'test_mode' => true
];

// โหลดการตั้งค่าจากไฟล์
function loadPaymentGatewayConfig() {
    global $configFile, $defaultConfig;
    
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        if ($config) {
            return array_merge($defaultConfig, $config);
        }
    }
    
    return $defaultConfig;
}

// บันทึกการตั้งค่า
function savePaymentGatewayConfig($config) {
    global $configFile;
    
    try {
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// ตรวจสอบการตั้งค่า
$currentConfig = loadPaymentGatewayConfig();

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_config':
                $newConfig = [
                    'enabled' => isset($_POST['enabled']) ? true : false,
                    'provider' => $_POST['provider'] ?? '',
                    'api_key' => $_POST['api_key'] ?? '',
                    'api_secret' => $_POST['api_secret'] ?? '',
                    'merchant_id' => $_POST['merchant_id'] ?? '',
                    'sandbox_mode' => isset($_POST['sandbox_mode']) ? true : false,
                    'callback_url' => $_POST['callback_url'] ?? '',
                    'test_mode' => isset($_POST['test_mode']) ? true : false
                ];
                
                if (savePaymentGatewayConfig($newConfig)) {
                    $currentConfig = $newConfig;
                    $successMessage = 'บันทึกการตั้งค่าเรียบร้อยแล้ว!';
                } else {
                    $errorMessage = 'เกิดข้อผิดพลาดในการบันทึกการตั้งค่า';
                }
                break;
                
            case 'test_connection':
                $testResult = testPaymentGatewayConnection($currentConfig);
                $_SESSION['test_result'] = $testResult;
                break;
                
            case 'test_qr_generation':
                $testResult = testQRCodeGeneration($currentConfig, 1000, 'TEST001');
                $_SESSION['test_result'] = $testResult;
                break;
        }
    }
}

// ฟังก์ชันทดสอบการเชื่อมต่อ
function testPaymentGatewayConnection($config) {
    if (!$config['enabled']) {
        return [
            'success' => false,
            'error' => 'Payment Gateway ยังไม่ได้เปิดใช้งาน'
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
    return testTrueMoneyQRGeneration($config, $amount, $orderId);
}

// ทดสอบการสร้าง QR Code KBank
function testKBankQRGeneration($config, $amount, $orderId) {
    return testTrueMoneyQRGeneration($config, $amount, $orderId);
}
?>

<!DOCTYPE html
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า Payment Gateway - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><i class="fas fa-cog me-2"></i>ตั้งค่า Payment Gateway</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $errorMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- แสดงผลลัพธ์การทดสอบ -->
        <?php if (isset($_SESSION['test_result'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3><i class="fas fa-flask me-2"></i>ผลลัพธ์การทดสอบ</h3>
                </div>
                <div class="card-body">
                    <?php $result = $_SESSION['test_result']; ?>
                    <?php if ($result['success']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $result['message']; ?>
                        </div>
                                    <!-- QR Code URL ถูกลบออกแล้ว -->
                        <?php if (isset($result['transaction_id'])): ?>
                            <p><strong>Transaction ID:</strong> <?php echo $result['transaction_id']; ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $result['error']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php unset($_SESSION['test_result']); ?>
        <?php endif; ?>
        
        <!-- ฟอร์มตั้งค่า Payment Gateway -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-credit-card me-2"></i>การตั้งค่า Payment Gateway</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="save_config">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="enabled" name="enabled" <?php echo $currentConfig['enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enabled">
                                        <strong>เปิดใช้งาน Payment Gateway</strong>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="provider" class="form-label">Provider</label>
                                <select class="form-select" id="provider" name="provider" required>
                                    <option value="">เลือก Provider</option>
                                    <option value="truemoney" <?php echo $currentConfig['provider'] === 'truemoney' ? 'selected' : ''; ?>>TrueMoney</option>
                                    <option value="scb" <?php echo $currentConfig['provider'] === 'scb' ? 'selected' : ''; ?>>SCB Easy Pay</option>
                                    <option value="kbank" <?php echo $currentConfig['provider'] === 'kbank' ? 'selected' : ''; ?>>KBank Payment Gateway</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="api_key" class="form-label">API Key</label>
                                <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($currentConfig['api_key']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="api_secret" class="form-label">API Secret</label>
                                <input type="password" class="form-control" id="api_secret" name="api_secret" value="<?php echo htmlspecialchars($currentConfig['api_secret']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="merchant_id" class="form-label">Merchant ID</label>
                                <input type="text" class="form-control" id="merchant_id" name="merchant_id" value="<?php echo htmlspecialchars($currentConfig['merchant_id']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="callback_url" class="form-label">Callback URL</label>
                                <input type="url" class="form-control" id="callback_url" name="callback_url" value="<?php echo htmlspecialchars($currentConfig['callback_url']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="sandbox_mode" name="sandbox_mode" <?php echo $currentConfig['sandbox_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="sandbox_mode">
                                        <strong>Sandbox Mode (ระบบทดสอบ)</strong>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="test_mode" name="test_mode" <?php echo $currentConfig['test_mode'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="test_mode">
                                        <strong>โหมดทดสอบ</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- ปุ่มทดสอบ -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-flask me-2"></i>ทดสอบการเชื่อมต่อ</h3>
            </div>
            <div class="card-body">
                <p>เลือกการทดสอบที่ต้องการ:</p>
                <div class="d-grid gap-2 d-md-flex">
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="test_connection">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-plug me-2"></i>ทดสอบการเชื่อมต่อ
                        </button>
                    </form>
                    
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="test_qr_generation">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="fas fa-qrcode me-2"></i>ทดสอบการสร้าง QR Code
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- คำแนะนำ -->
        <div class="alert alert-info">
            <h4><i class="fas fa-info-circle me-2"></i>คำแนะนำ</h4>
            <ul class="mb-0">
                <li><strong>เริ่มจาก TrueMoney:</strong> เพราะข้อกำหนดไม่เข้มงวด และค่าธรรมเนียมต่ำ</li>
                <li><strong>ใช้ Sandbox Mode ก่อน:</strong> ทดสอบในระบบทดสอบก่อนใช้งานจริง</li>
                <li><strong>ทดสอบการเชื่อมต่อ:</strong> ตรวจสอบว่า API ทำงานได้หรือไม่</li>
                <li><strong>ทดสอบการสร้าง QR Code:</strong> ตรวจสอบว่าสร้าง QR Code ได้หรือไม่</li>
            </ul>
        </div>
        
        <hr>
        <p><a href="payment_gateway_test.php" class="btn btn-primary">ระบบทดสอบ Payment Gateway</a></p>
        <p><a href="application_documents.php" class="btn btn-secondary">เอกสารสำหรับสมัคร</a></p>
        <p><a href="test_qr_fake_complete.php" class="btn btn-info">กลับไปหน้าทดสอบ</a></p>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
