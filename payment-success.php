<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header('Location: index.php');
    exit();
}

try {
    $conn = getConnection();
    
    // ดึงข้อมูลคำสั่งซื้อที่ชำระเงินแล้ว
    $orderQuery = "SELECT o.*, u.username 
                   FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.id 
                   WHERE o.id = $1 AND o.user_id = $2 AND o.order_status = 'paid'";
    
    $orderResult = pg_query_params($conn, $orderQuery, [$order_id, $_SESSION['user_id']]);
    
    if (!$orderResult || pg_num_rows($orderResult) == 0) {
        throw new Exception('ไม่พบคำสั่งซื้อที่ชำระเงินแล้ว');
    }
    
    $order = pg_fetch_assoc($orderResult);
    
    // ดึงข้อมูลสินค้าในคำสั่งซื้อ
    $itemsQuery = "SELECT * FROM order_items WHERE order_id = $1";
    $itemsResult = pg_query_params($conn, $itemsQuery, [$order_id]);
    
    $orderItems = [];
    if ($itemsResult) {
        while ($item = pg_fetch_assoc($itemsResult)) {
            $orderItems[] = $item;
        }
    }
    
    // ดึงข้อมูลการชำระเงิน
    $paymentQuery = "SELECT * FROM payments WHERE order_id = $1 AND payment_status = 'paid'";
    $paymentResult = pg_query_params($conn, $paymentQuery, [$order_id]);
    
    $payment = null;
    if ($paymentResult && pg_num_rows($paymentResult) > 0) {
        $payment = pg_fetch_assoc($paymentResult);
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
} finally {
    if (isset($conn)) {
        pg_close($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZapShop - ชำระเงินสำเร็จ</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/zapshop-design-system.css" rel="stylesheet">
    <style>
        .success-container {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 40px 0;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .receipt-section {
            padding: 30px;
        }
        .receipt-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .total-section {
            background: #e8f5e8;
            border-radius: 15px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .action-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .btn-success-custom {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            margin: 10px;
            transition: all 0.3s ease;
        }
        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="container">
            <?php if (isset($error)): ?>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="alert alert-danger">
                            <h4>เกิดข้อผิดพลาด</h4>
                            <p><?php echo htmlspecialchars($error); ?></p>
                            <a href="index.php" class="btn btn-primary">กลับหน้าหลัก</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="success-card">
                            <!-- หัวข้อสำเร็จ -->
                            <div class="success-header">
                                <div class="success-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h1>ชำระเงินสำเร็จ!</h1>
                                <p class="mb-0">ขอบคุณที่ใช้บริการ ZapShop</p>
                                <p class="mb-0">Order: <?php echo htmlspecialchars($order['order_number']); ?></p>
                            </div>

                            <!-- ใบเสร็จ -->
                            <div class="receipt-section">
                                <h3 class="text-center mb-4">
                                    <i class="fas fa-receipt"></i> ใบเสร็จการชำระเงิน
                                </h3>

                                <!-- ข้อมูลการชำระเงิน -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-info-circle"></i> ข้อมูลการชำระเงิน</h5>
                                        <p><strong>วันที่ชำระ:</strong> <?php echo date('d/m/Y H:i', strtotime($payment['payment_date'])); ?></p>
                                        <p><strong>วิธีการชำระเงิน:</strong> <?php echo ucfirst($payment['payment_method']); ?></p>
                                        <p><strong>สถานะ:</strong> <span class="badge badge-success">สำเร็จ</span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5><i class="fas fa-shipping-fast"></i> ข้อมูลการจัดส่ง</h5>
                                        <p><strong>ที่อยู่:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                        <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                                        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($order['shipping_email']); ?></p>
                                    </div>
                                </div>

                                <!-- รายการสินค้า -->
                                <h5><i class="fas fa-shopping-cart"></i> รายการสินค้า</h5>
                                <?php if (!empty($orderItems)): ?>
                                    <?php foreach ($orderItems as $item): ?>
                                        <div class="receipt-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                                    <small class="text-muted">จำนวน: <?php echo $item['quantity']; ?> ชิ้น</small>
                                                </div>
                                                <div class="col-md-3 text-center">
                                                    <span class="text-muted">฿<?php echo number_format($item['unit_price'], 2); ?></span>
                                                </div>
                                                <div class="col-md-3 text-end">
                                                    <strong>฿<?php echo number_format($item['total_price'], 2); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <!-- ยอดรวม -->
                                <div class="total-section">
                                    <h4>ยอดรวมทั้งหมด</h4>
                                    <h2 class="text-success">฿<?php echo number_format($order['total_amount'], 2); ?></h2>
                                    <p class="text-muted mb-0">ชำระแล้วเมื่อ <?php echo date('d/m/Y เวลา H:i', strtotime($payment['payment_date'])); ?></p>
                                </div>

                                <!-- หมายเหตุ -->
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> หมายเหตุ</h6>
                                    <ul class="mb-0">
                                        <li>คำสั่งซื้อของคุณจะถูกประมวลผลภายใน 24 ชั่วโมง</li>
                                        <li>คุณจะได้รับอีเมลยืนยันการสั่งซื้อในไม่ช้า</li>
                                        <li>หากมีคำถาม กรุณาติดต่อฝ่ายบริการลูกค้า</li>
                                    </ul>
                                </div>

                                <!-- ปุ่มดำเนินการ -->
                                <div class="action-buttons">
                                    <a href="orders.php" class="btn btn-success-custom">
                                        <i class="fas fa-list"></i> ดูคำสั่งซื้อทั้งหมด
                                    </a>
                                    <a href="index.php" class="btn btn-success-custom">
                                        <i class="fas fa-shopping-bag"></i> ช้อปปิ้งต่อ
                                    </a>
                                    <button onclick="window.print()" class="btn btn-success-custom">
                                        <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    
    <script>
        // แสดงข้อความต้อนรับ
        setTimeout(() => {
            if (typeof showWelcomeMessage === 'function') {
                showWelcomeMessage();
            }
        }, 1000);

        // ฟังก์ชันแสดงข้อความต้อนรับ
        function showWelcomeMessage() {
            const welcomeDiv = document.createElement('div');
            welcomeDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
            welcomeDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            welcomeDiv.innerHTML = `
                <i class="fas fa-heart"></i>
                <strong>ขอบคุณที่ใช้บริการ!</strong><br>
                การสั่งซื้อของคุณเสร็จสมบูรณ์แล้ว
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(welcomeDiv);
            
            // ลบข้อความหลังจาก 5 วินาที
            setTimeout(() => {
                if (welcomeDiv.parentNode) {
                    welcomeDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>
