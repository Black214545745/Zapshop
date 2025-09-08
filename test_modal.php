<?php
// ไฟล์ทดสอบ QR Modal
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 1000;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบ QR Modal - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>ทดสอบ QR Modal</h1>
        <p>จำนวนเงิน: ฿<?php echo number_format($amount, 2); ?></p>
        
        <!-- ปุ่มเปิด Modal -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#qrPaymentModal">
            <i class="fas fa-qrcode me-2"></i>เปิด QR Payment Modal
        </button>
        
        <hr>
        <p><a href="test_qr_fake_complete.php">กลับไปหน้าทดสอบ</a></p>
    </div>

    <!-- Include QR Payment Modal -->
    <!-- ระบบ QR Code ถูกลบออกแล้ว -->
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    ระบบ QR Code ถูกลบออกแล้ว กรุณาใช้วิธีการชำระเงินอื่น
</div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
