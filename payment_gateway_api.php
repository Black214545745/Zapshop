<?php
/**
 * Payment Gateway API Integration
 * โค้ดสำหรับเชื่อมต่อ Payment Gateway จริง
 * ใช้หลังจากได้รับการอนุมัติจากธนาคาร
 */

// ตัวอย่างการเชื่อมต่อ TrueMoney API (ต้องได้รับข้อมูลจริงจากธนาคาร)
class TrueMoneyPaymentGateway {
    private $apiKey;
    private $apiSecret;
    private $merchantId;
    private $sandboxMode;
    private $baseUrl;
    
    public function __construct($apiKey, $apiSecret, $merchantId, $sandboxMode = true) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->merchantId = $merchantId;
        $this->sandboxMode = $sandboxMode;
        
        // ใช้ URL ตามโหมด (Sandbox หรือ Production)
        $this->baseUrl = $sandboxMode 
            ? 'https://sandbox-api.truemoney.com' 
            : 'https://api.truemoney.com';
    }
    
    /**
     * สร้าง Dynamic QR Code
     */
    public function generateDynamicQR($amount, $orderId, $reference1 = '', $reference2 = '') {
        $endpoint = $this->baseUrl . '/v1/qr/generate';
        
        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'currency' => 'THB',
            'order_id' => $orderId,
            'reference1' => $reference1,
            'reference2' => $reference2,
            'expiry' => 1800, // 30 นาที
            'callback_url' => 'https://zapshop.com/payment/callback'
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'X-API-Key: ' . $this->apiKey
        ];
        
        try {
            $response = $this->makeRequest($endpoint, 'POST', $payload, $headers);
            
            if ($response['success']) {
                return [
                    'success' => true,
                                    // 'qr_code_url' => $response['data']['qr_code_url'], // ถูกลบออกแล้ว
                // 'qr_code_image' => $response['data']['qr_code_image'], // ถูกลบออกแล้ว
                    'transaction_id' => $response['data']['transaction_id'],
                    'expires_at' => $response['data']['expires_at']
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Unknown error'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'API Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ตรวจสอบสถานะการชำระเงิน
     */
    public function checkPaymentStatus($transactionId) {
        $endpoint = $this->baseUrl . '/v1/transaction/status/' . $transactionId;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'X-API-Key: ' . $this->apiKey
        ];
        
        try {
            $response = $this->makeRequest($endpoint, 'GET', null, $headers);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'status' => $response['data']['status'],
                    'amount' => $response['data']['amount'],
                    'paid_at' => $response['data']['paid_at'] ?? null,
                    'reference' => $response['data']['reference'] ?? null
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Unknown error'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'API Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ยกเลิกการชำระเงิน
     */
    public function cancelPayment($transactionId) {
        $endpoint = $this->baseUrl . '/v1/transaction/cancel/' . $transactionId;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'X-API-Key' => $this->apiKey
        ];
        
        try {
            $response = $this->makeRequest($endpoint, 'POST', null, $headers);
            
            return [
                'success' => $response['success'],
                'message' => $response['message'] ?? 'Payment cancelled'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'API Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * ส่ง HTTP Request
     */
    private function makeRequest($url, $method, $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('HTTP Error: ' . $httpCode);
        }
        
        return json_decode($response, true);
    }
}

// ตัวอย่างการใช้งาน (ต้องได้รับข้อมูลจริงจากธนาคาร)
if (isset($_GET['test']) && $_GET['test'] === 'true') {
    echo "<h1>ทดสอบ Payment Gateway API</h1>";
    echo "<p><strong>หมายเหตุ:</strong> นี่เป็นเพียงตัวอย่างโค้ด ต้องได้รับข้อมูลจริงจากธนาคารก่อนใช้งาน</p>";
    
    // ตัวอย่างการใช้งาน (ข้อมูลปลอม)
    $gateway = new TrueMoneyPaymentGateway(
        'your_api_key_here',      // ต้องได้รับจากธนาคาร
        'your_api_secret_here',   // ต้องได้รับจากธนาคาร
        'your_merchant_id_here',  // ต้องได้รับจากธนาคาร
        true                      // Sandbox mode
    );
    
    echo "<h3>ตัวอย่างการสร้าง QR Code:</h3>";
    echo "<pre>";
    echo "// สร้าง Dynamic QR Code\n";
    echo "\$result = \$gateway->generateDynamicQR(1000, 'ORD001');\n";
    echo "if (\$result['success']) {\n";
    // echo "    echo 'QR Code URL: ' . \$result['qr_code_url'];\n"; // ถูกลบออกแล้ว
    echo "} else {\n";
    echo "    echo 'Error: ' . \$result['error'];\n";
    echo "}\n";
    echo "</pre>";
    
    echo "<h3>ตัวอย่างการตรวจสอบสถานะ:</h3>";
    echo "<pre>";
    echo "// ตรวจสอบสถานะการชำระเงิน\n";
    echo "\$status = \$gateway->checkPaymentStatus('transaction_id');\n";
    echo "if (\$status['success']) {\n";
    echo "    echo 'Status: ' . \$status['status'];\n";
    echo "    echo 'Amount: ' . \$status['amount'];\n";
    echo "}\n";
    echo "</pre>";
    
    echo "<hr>";
    echo "<p><a href='payment_gateway_info.php'>ดูข้อมูล Payment Gateway</a></p>";
    echo "<p><a href='test_qr_fake_complete.php'>กลับไปหน้าทดสอบ</a></p>";
}
?>
