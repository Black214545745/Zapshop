<?php
session_start();
require_once 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit();
}

// รับ order_id จาก URL
$order_id = $_GET['order_id'] ?? '';

if (empty($order_id)) {
    header('Location: cart.php');
    exit();
}

// ดึงข้อมูล order
    $conn = getConnection();
$orderQuery = "SELECT o.*, up.full_name, up.email, up.phone, up.address 
                    FROM orders o 
               LEFT JOIN user_profiles up ON o.user_id = up.user_id 
               WHERE o.id = $1";
$orderResult = pg_query_params($conn, $orderQuery, [$order_id]);

if (!$orderResult || pg_num_rows($orderResult) == 0) {
    header('Location: cart.php');
    exit();
}

$order = pg_fetch_assoc($orderResult);

// ดึงข้อมูล order details
$detailsQuery = "SELECT * FROM order_details WHERE order_id = $1";
$detailsResult = pg_query_params($conn, $detailsQuery, [$order_id]);
$orderDetails = [];

if ($detailsResult) {
    while ($row = pg_fetch_assoc($detailsResult)) {
        $orderDetails[] = $row;
    }
}

    pg_close($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สั่งซื้อสำเร็จ - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --makro-red: #e31837;
            --makro-red-dark: #c41230;
            --makro-orange: #ff6b35;
            --makro-yellow: #ffd23f;
            --makro-blue: #0066cc;
            --makro-green: #28a745;
            --makro-gray: #6c757d;
            --makro-light-gray: #f8f9fa;
            --makro-border: #e9ecef;
            --makro-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            --makro-shadow-hover: 0 4px 16px rgba(0, 0, 0, 0.15);
            --makro-radius: 12px;
            --makro-radius-sm: 8px;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            color: #2c3e50;
        }

        .success-header {
            background: linear-gradient(135deg, var(--makro-green) 0%, #20c997 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 40px;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .success-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .success-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .order-info-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .order-info-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--makro-light-gray);
        }

        .order-info-icon {
            color: var(--makro-green);
            font-size: 1.5rem;
        }

        .order-info-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--makro-green);
            margin: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item {
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            padding: 20px;
            border-left: 4px solid var(--makro-green);
        }

        .info-label {
            font-size: 0.9rem;
            color: var(--makro-gray);
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .order-details-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }

        .order-details-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--makro-light-gray);
        }

        .order-details-icon {
            color: var(--makro-blue);
            font-size: 1.5rem;
        }

        .order-details-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--makro-blue);
            margin: 0;
        }

        .order-item {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid var(--makro-border);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .item-price {
            color: var(--makro-gray);
            font-size: 0.9rem;
        }

        .item-quantity {
            text-align: center;
            font-weight: 600;
            color: #2c3e50;
        }

        .item-total {
            text-align: right;
            font-weight: 700;
            color: var(--makro-red);
            font-size: 1.1rem;
        }

        .order-summary {
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            padding: 20px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
        }

        .summary-row:last-child {
            border-top: 2px solid var(--makro-border);
            margin-top: 10px;
            padding-top: 20px;
        }

        .summary-label {
            color: var(--makro-gray);
        }

        .summary-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .total-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--makro-red);
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 15px 30px;
            border-radius: var(--makro-radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--makro-blue) 0%, #0056b3 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #0056b3 0%, var(--makro-blue) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--makro-green) 0%, #20c997 100%);
            color: white;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, var(--makro-green) 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }

        @media (max-width: 768px) {
            .order-item {
                grid-template-columns: 1fr;
                gap: 10px;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-action {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <?php include 'include/menu.php'; ?>
    
<div class="success-header">
    <div class="container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="success-title">สั่งซื้อสำเร็จ!</h1>
        <p class="success-subtitle">ขอบคุณสำหรับการสั่งซื้อสินค้าจาก ZapShop</p>
    </div>
            </div>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="order-info-card">
                <div class="order-info-header">
                    <i class="fas fa-receipt order-info-icon"></i>
                    <h2 class="order-info-title">ข้อมูลการสั่งซื้อ</h2>
            </div>
            
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">หมายเลขคำสั่งซื้อ</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['id']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">วันที่สั่งซื้อ</div>
                        <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></div>
                </div>
                
                    <div class="info-item">
                        <div class="info-label">วิธีการชำระเงิน</div>
                        <div class="info-value">
                            <?php
                            $paymentMethods = [
                                'promptpay' => 'QR พร้อมเพย์',
                                'credit_card' => 'บัตรเครดิต/บัตรเดบิต',
                                'bank_transfer' => 'โอนเงินผ่านธนาคาร',
                                'mobile_banking' => 'Mobile Banking',
                                'cash_on_delivery' => 'เก็บเงินปลายทาง',
                                'digital_wallet' => 'ดิจิทัลวอลเล็ตร้านค้า'
                            ];
                            echo $paymentMethods[$order['payment_method']] ?? $order['payment_method'];
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">สถานะ</div>
                        <div class="info-value">
                            <span class="badge bg-warning"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                    </div>
                </div>
                    </div>
                    
            <div class="order-details-card">
                <div class="order-details-header">
                    <i class="fas fa-shopping-bag order-details-icon"></i>
                    <h2 class="order-details-title">รายการสินค้า</h2>
                    </div>
                    
                <?php foreach ($orderDetails as $item): ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-price">฿<?php echo number_format($item['price'], 2); ?> ต่อชิ้น</div>
                    </div>
                    <div class="item-quantity"><?php echo $item['quantity']; ?> ชิ้น</div>
                    <div class="item-total">฿<?php echo number_format($item['total'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                
                <div class="order-summary">
                    <div class="summary-row">
                        <span class="summary-label">ยอดรวมสินค้า</span>
                        <span class="summary-value">฿<?php echo number_format($order['grand_total'] - 38, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">ค่าจัดส่ง</span>
                        <span class="summary-value">฿38.00</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">ยอดชำระเงินทั้งหมด</span>
                        <span class="summary-value total-amount">฿<?php echo number_format($order['grand_total'], 2); ?></span>
                    </div>
                </div>
            </div>
                    </div>
                    
        <div class="col-lg-4">
            <div class="order-info-card">
                <div class="order-info-header">
                    <i class="fas fa-user order-info-icon"></i>
                    <h2 class="order-info-title">ข้อมูลผู้รับ</h2>
                    </div>
                    
                <div class="info-item">
                    <div class="info-label">ชื่อ-นามสกุล</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['fullname']); ?></div>
                    </div>
                    
                <div class="info-item">
                    <div class="info-label">เบอร์โทรศัพท์</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['tel']); ?></div>
                    </div>
                    
                <div class="info-item">
                    <div class="info-label">อีเมล</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['email']); ?></div>
                    </div>
                    
                <div class="info-item">
                    <div class="info-label">ที่อยู่จัดส่ง</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($order['address'])); ?></div>
                </div>
            </div>
                    </div>
                </div>
            
            <div class="action-buttons">
        <a href="product-list1.php" class="btn-action btn-primary">
            <i class="fas fa-shopping-bag"></i> ช้อปปิ้งต่อ
                </a>
        <a href="orders.php" class="btn-action btn-success">
            <i class="fas fa-list"></i> ดูคำสั่งซื้อ
                </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
