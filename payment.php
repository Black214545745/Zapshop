<?php
session_start();
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    // หากยังไม่ได้เข้าสู่ระบบ ให้รีไดเร็คไปที่หน้าเข้าสู่ระบบ
    header("Location: user-login.php");
    exit();
}
include 'config.php';
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payment</title>
  <link href="<?php echo $base_url; ?>/assets/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { 
      background-color: #f8f9fa; 
    }
    .payment-container {
      margin-top: 30px;
      max-width: 600px;  /* กำหนดความกว้างของคอนเทนเนอร์ */
      margin-left: auto;
      margin-right: auto;
    }
    .card {
      margin-bottom: 20px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .card-header {
      background-color: #007bff;
      color: #fff;
    }
    .btn-primary {
      width: 100%; /* ปุ่ม Submit ครอบคลุมความกว้างของการ์ด */
    }
  </style>
</head>
<body class="bg-body-tertiary">
  <?php include 'include/menu.php'; ?>
  <div class="container payment-container">
    <?php if (!empty($_SESSION['message'])): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    <div class="card">
      <div class="card-header">
        <h4 class="mb-0">Payment Information</h4>
      </div>
      <div class="card-body">
        <form action="<?php echo $base_url; ?>/payment-process.php" method="post">
          <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select" required>
              <option value="">-- เลือกวิธีการชำระเงิน --</option>
              <option value="credit_card">Credit Card</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="paypal">PayPal</option>
                              <!-- ตัวเลือก QR Code ถูกลบออกแล้ว -->
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" step="0.01" name="amount" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Currency</label>
            <input type="text" name="currency" class="form-control" value="THB" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Transaction ID</label>
            <input type="text" name="transaction_id" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Payment Status</label>
            <select name="payment_status" class="form-select" required>
              <option value="completed">Completed</option>
              <option value="pending">Pending</option>
              <option value="failed">Failed</option>
            </select>
          </div>
          <!-- หากมี order_id เก็บไว้ใน session หรือส่งผ่าน hidden field -->
          <input type="hidden" name="order_id" value="<?php echo isset($_SESSION['order_id']) ? intval($_SESSION['order_id']) : 0; ?>">
          <button type="submit" class="btn btn-primary">Submit Payment</button>
        </form>
      </div>
    </div>
  </div>
  <script src="<?php echo $base_url; ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
