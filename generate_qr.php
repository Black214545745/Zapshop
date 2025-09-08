<?php
require_once 'config.php';

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// รับข้อมูล
$order_id = intval($_POST['order_id'] ?? 0);
$amount = floatval($_POST['amount'] ?? 0);

if ($order_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit();
}

try {
    $conn = getConnection();
    
    // ตรวจสอบว่ามี order นี้อยู่จริงหรือไม่
    $checkQuery = "SELECT o.id, o.order_number, p.payment_status 
                   FROM orders o 
                   LEFT JOIN payments p ON o.id = p.order_id 
                   WHERE o.id = $1";
    
    $checkResult = pg_query_params($conn, $query, [$order_id]);
    
    if (!$checkResult || pg_num_rows($checkResult) == 0) {
        throw new Exception('ไม่พบคำสั่งซื้อนี้');
    }
    
    $orderData = pg_fetch_assoc($checkResult);
    
    // ตรวจสอบว่ายังไม่ได้ชำระเงิน
    if ($orderData['payment_status'] === 'paid') {
        throw new Exception('คำสั่งซื้อนี้ได้ชำระเงินแล้ว');
    }
    
    // PromptPay ID ของร้านค้า (ควรเก็บใน config หรือ database)
    $promptpay_id = "0812345678"; // เปลี่ยนเป็น ID จริงของร้านค้า
    
    // สร้าง QR Code String สำหรับ PromptPay
    // ใช้รูปแบบ EMV QR Code สำหรับ PromptPay
    $qrString = generatePromptPayQR($promptpay_id, $amount, $orderData['order_number']);
    
    // อัปเดต payments.qr_code_generated
    $updateQuery = "UPDATE payments SET qr_code_generated = true WHERE order_id = $1";
    $updateResult = pg_query_params($conn, $updateQuery, [$order_id]);
    
    if (!$updateResult) {
        throw new Exception('ไม่สามารถอัปเดตสถานะ QR Code ได้');
    }
    
    pg_close($conn);
    
    echo json_encode([
        'success' => true,
        'qrString' => $qrString,
        'order_number' => $orderData['order_number'],
        'amount' => $amount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * สร้าง QR Code String สำหรับ PromptPay
 * ใช้รูปแบบ EMV QR Code ตามมาตรฐาน PromptPay
 */
function generatePromptPayQR($promptpay_id, $amount, $reference = '') {
    // รูปแบบ EMV QR Code สำหรับ PromptPay
    $qrData = [];
    
    // ข้อมูลร้านค้า
    $qrData[] = "000201"; // Payload Format Indicator
    $qrData[] = "010212"; // Point of Initiation Method (12 = QR Code)
    
    // ข้อมูล PromptPay
    $qrData[] = "2662"; // Merchant Account Information
    $qrData[] = "0016A000000677010112"; // Global Unique Identifier
    $qrData[] = "0112" . $promptpay_id; // PromptPay ID
    
    // ข้อมูลร้านค้า
    $qrData[] = "52"; // Merchant Category Code
    $qrData[] = "0000"; // General
    
    // สกุลเงิน
    $qrData[] = "53"; // Transaction Currency
    $qrData[] = "764"; // THB (Thai Baht)
    
    // ยอดเงิน
    $qrData[] = "54"; // Transaction Amount
    $qrData[] = sprintf("%.2f", $amount); // รูปแบบ 2 ทศนิยม
    
    // ประเทศ
    $qrData[] = "58"; // Country Code
    $qrData[] = "TH"; // Thailand
    
    // ข้อมูลเพิ่มเติม
    if (!empty($reference)) {
        $qrData[] = "62"; // Additional Data Field Template
        $qrData[] = "05" . $reference; // Reference Label (05) + Reference
    }
    
    // CRC
    $qrString = implode('', $qrData);
    $crc = strtoupper(dechex(crc32($qrString)));
    $qrString .= "6304" . $crc;
    
    return $qrString;
}

/**
 * ฟังก์ชัน CRC32 สำหรับคำนวณ checksum
 */
function crc32($string) {
    return crc32($string);
}
?>
