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
    
    // ดึงข้อมูลคำสั่งซื้อ
    $orderQuery = "SELECT o.*, u.username 
                   FROM orders o 
                   LEFT JOIN users u ON o.user_id = u.id 
                   WHERE o.id = $1 AND o.user_id = $2";
    
    $orderResult = pg_query_params($conn, $orderQuery, [$order_id, $_SESSION['user_id']]);
    
    if (!$orderResult || pg_num_rows($orderResult) == 0) {
        throw new Exception('ไม่พบคำสั่งซื้อนี้');
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
    $paymentQuery = "SELECT * FROM payments WHERE order_id = $1";
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
    <title>ZapShop - สถานะการชำระเงิน</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/zapshop-design-system.css" rel="stylesheet">
    <style>
        .status-card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .status-pending {
            border-left: 5px solid #ffc107;
        }
        .status-paid {
            border-left: 5px solid #28a745;
        }
        .status-failed {
            border-left: 5px solid #dc3545;
        }
        .order-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .item-row {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #e9ecef;
        }
        .refresh-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .refresh-btn:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <h4>เกิดข้อผิดพลาด</h4>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="index.php" class="btn btn-primary">กลับหน้าหลัก</a>
            </div>
        <?php else: ?>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <!-- หัวข้อ -->
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-receipt"></i> สถานะการชำระเงิน</h2>
                        <p class="text-muted">Order: <?php echo htmlspecialchars($order['order_number']); ?></p>
                    </div>

                    <!-- สถานะการชำระเงิน -->
                    <div class="card status-card <?php echo 'status-' . $order['order_status']; ?>">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> สถานะคำสั่งซื้อ</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>สถานะ:</strong> 
                                        <span class="badge badge-<?php echo $order['order_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                            <?php echo $order['order_status'] === 'paid' ? 'ชำระเงินแล้ว' : 'รอการชำระเงิน'; ?>
                                        </span>
                                    </p>
                                    <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                                    <p><strong>ยอดรวม:</strong> <span class="h5 text-primary">฿<?php echo number_format($order['total_amount'], 2); ?></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>วิธีการชำระเงิน:</strong> <?php echo $payment ? ucfirst($payment['payment_method']) : 'ไม่ระบุ'; ?></p>
                                    <p><strong>สถานะการชำระเงิน:</strong> 
                                        <span class="badge badge-<?php echo ($payment && $payment['payment_status'] === 'paid') ? 'success' : 'warning'; ?>">
                                            <?php echo ($payment && $payment['payment_status'] === 'paid') ? 'สำเร็จ' : 'รอการชำระเงิน'; ?>
                                        </span>
                                    </p>
                                    <?php if ($payment && $payment['payment_status'] === 'paid' && $payment['payment_date']): ?>
                                        <p><strong>วันที่ชำระ:</strong> <?php echo date('d/m/Y H:i', strtotime($payment['payment_date'])); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- ปุ่มตรวจสอบสถานะ -->
                            <div class="text-center mt-3">
                                <button class="refresh-btn" onclick="checkPaymentStatus()">
                                    <i class="fas fa-sync-alt"></i> ตรวจสอบสถานะล่าสุด
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลการจัดส่ง -->
                    <div class="card status-card">
                        <div class="card-header">
                            <h5><i class="fas fa-shipping-fast"></i> ข้อมูลการจัดส่ง</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ที่อยู่:</strong> <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                    <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($order['shipping_email']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- รายการสินค้า -->
                    <div class="card status-card">
                        <div class="card-header">
                            <h5><i class="fas fa-shopping-cart"></i> รายการสินค้า</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($orderItems)): ?>
                                <?php foreach ($orderItems as $item): ?>
                                    <div class="item-row">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h6><?php echo htmlspecialchars($item['product_name']); ?></h6>
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
                                
                                <div class="text-end mt-3">
                                    <h5>ยอดรวม: <span class="text-primary">฿<?php echo number_format($order['total_amount'], 2); ?></span></h5>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">ไม่พบรายการสินค้า</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ปุ่มดำเนินการ -->
                    <div class="text-center mt-4">
                        <?php if ($order['order_status'] === 'paid'): ?>
                            <a href="payment-success.php?order_id=<?php echo $order_id; ?>" class="btn btn-success">
                                <i class="fas fa-check-circle"></i> ดูใบเสร็จ
                            </a>
                        <?php else: ?>
                            <a href="checkout.php" class="btn btn-primary">
                                <i class="fas fa-qrcode"></i> ชำระเงินด้วย QR Code
                            </a>
                        <?php endif; ?>
                        
                        <a href="orders.php" class="btn btn-info">
                            <i class="fas fa-list"></i> ดูคำสั่งซื้อทั้งหมด
                        </a>
                        
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> กลับหน้าหลัก
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // ตรวจสอบสถานะการชำระเงิน
        async function checkPaymentStatus() {
            const orderId = <?php echo $order_id; ?>;
            const refreshBtn = document.querySelector('.refresh-btn');
            const originalText = refreshBtn.innerHTML;
            
            try {
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...';
                refreshBtn.disabled = true;
                
                const response = await fetch("check_payment_status.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "order_id=" + orderId
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // อัปเดตสถานะในหน้า
                    updatePaymentStatus(data);
                    
                    // แสดงข้อความสำเร็จ
                    showAlert('ตรวจสอบสถานะสำเร็จ', 'success');
                    
                    // รีโหลดหน้าหากชำระเงินแล้ว
                    if (data.is_paid) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                } else {
                    showAlert('ไม่สามารถตรวจสอบสถานะได้: ' + data.message, 'danger');
                }
            } catch (error) {
                console.error("Error checking payment status:", error);
                showAlert('เกิดข้อผิดพลาดในการตรวจสอบสถานะ', 'danger');
            } finally {
                refreshBtn.innerHTML = originalText;
                refreshBtn.disabled = false;
            }
        }

        // อัปเดตสถานะการชำระเงิน
        function updatePaymentStatus(data) {
            // อัปเดตสถานะคำสั่งซื้อ
            const statusBadge = document.querySelector('.status-card .badge');
            if (statusBadge) {
                if (data.is_paid) {
                    statusBadge.className = 'badge badge-success';
                    statusBadge.textContent = 'ชำระเงินแล้ว';
                } else {
                    statusBadge.className = 'badge badge-warning';
                    statusBadge.textContent = 'รอการชำระเงิน';
                }
            }
            
            // อัปเดตสถานะการชำระเงิน
            const paymentStatusBadge = document.querySelectorAll('.status-card .badge')[1];
            if (paymentStatusBadge) {
                if (data.payment_status === 'paid') {
                    paymentStatusBadge.className = 'badge badge-success';
                    paymentStatusBadge.textContent = 'สำเร็จ';
                } else {
                    paymentStatusBadge.className = 'badge badge-warning';
                    paymentStatusBadge.textContent = 'รอการชำระเงิน';
                }
            }
        }

        // แสดงข้อความแจ้งเตือน
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // ลบข้อความหลังจาก 5 วินาที
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>

    <script src="assets/js/bootstrap.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
</body>
</html>
