<?php
session_start();
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

include 'config.php';

// ดึงข้อมูลคำสั่งซื้อของผู้ใช้
$user_id = $_SESSION['user_id'];
$conn = getConnection();

// จัดการการยกเลิกคำสั่งซื้อ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];
    
    // ตรวจสอบว่าเป็นคำสั่งซื้อของผู้ใช้นี้จริงหรือไม่
    $check_query = "SELECT id, status FROM orders WHERE id = $1 AND user_id = $2";
    $check_result = pg_query_params($conn, $check_query, [$order_id, $user_id]);
    
    if ($check_result && pg_num_rows($check_result) > 0) {
        $order = pg_fetch_assoc($check_result);
        
        if ($order['status'] === 'pending') {
            // อัปเดตสถานะเป็นยกเลิก
            $cancel_query = "UPDATE orders SET status = 'cancelled' WHERE id = $1";
            $cancel_result = pg_query_params($conn, $cancel_query, [$order_id]);
            
            if ($cancel_result) {
                $_SESSION['message'] = 'ยกเลิกคำสั่งซื้อสำเร็จ';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'เกิดข้อผิดพลาดในการยกเลิกคำสั่งซื้อ';
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'ไม่สามารถยกเลิกคำสั่งซื้อที่ดำเนินการแล้วได้';
            $_SESSION['message_type'] = 'warning';
        }
    } else {
        $_SESSION['message'] = 'ไม่พบคำสั่งซื้อนี้';
        $_SESSION['message_type'] = 'danger';
    }
    
    header("Location: orders.php");
    exit();
}

// ดึงรายการคำสั่งซื้อทั้งหมด
$orders_query = "
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.quantity * oi.price) as total_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $1
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$orders_result = pg_query_params($conn, $orders_query, [$user_id]);
$orders = [];

if ($orders_result) {
    while ($order = pg_fetch_assoc($orders_result)) {
        // ดึงรายละเอียดสินค้าในคำสั่งซื้อ
        $items_query = "
            SELECT oi.*, p.name as product_name, p.image_url
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = $1
        ";
        $items_result = pg_query_params($conn, $items_query, [$order['id']]);
        $order['items'] = [];
        
        if ($items_result) {
            while ($item = pg_fetch_assoc($items_result)) {
                $order['items'][] = $item;
            }
        }
        
        $orders[] = $order;
    }
}

pg_close($conn);

// บันทึก Activity Log
logActivity($_SESSION['user_id'], 'view', 'User viewed order history', 'orders', null);

// ฟังก์ชันสำหรับแสดงสถานะ
function getStatusBadge($status) {
    $status_map = [
        'pending' => ['text' => 'รอดำเนินการ', 'class' => 'warning'],
        'processing' => ['text' => 'กำลังดำเนินการ', 'class' => 'info'],
        'shipped' => ['text' => 'จัดส่งแล้ว', 'class' => 'primary'],
        'delivered' => ['text' => 'จัดส่งสำเร็จ', 'class' => 'success'],
        'cancelled' => ['text' => 'ยกเลิก', 'class' => 'danger']
    ];
    
    $status_info = $status_map[$status] ?? ['text' => $status, 'class' => 'secondary'];
    return "<span class='badge bg-{$status_info['class']}'>{$status_info['text']}</span>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการสั่งซื้อ - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --makro-red: #e31837;
            --makro-red-dark: #c41230;
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

        .page-header {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="0,0 1000,100 1000,0"/></svg>');
            background-size: cover;
        }

        .page-header-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        .order-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: var(--makro-shadow-hover);
            transform: translateY(-2px);
        }

        .order-header {
            background: var(--makro-light-gray);
            padding: 20px;
            border-bottom: 1px solid var(--makro-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .order-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--makro-red);
        }

        .order-date {
            color: var(--makro-gray);
            font-size: 0.9rem;
        }

        .order-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .btn-cancel {
            background: var(--makro-red);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--makro-radius-sm);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: var(--makro-red-dark);
            transform: translateY(-1px);
            color: white;
        }

        .order-items {
            padding: 20px;
        }

        .item-card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border: 1px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            margin-bottom: 15px;
            background: var(--makro-light-gray);
        }

        .item-image {
            width: 60px;
            height: 60px;
            border-radius: var(--makro-radius-sm);
            overflow: hidden;
            flex-shrink: 0;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .item-price {
            color: var(--makro-red);
            font-weight: 600;
        }

        .item-quantity {
            color: var(--makro-gray);
            font-size: 0.9rem;
        }

        .order-summary {
            background: var(--makro-light-gray);
            padding: 20px;
            border-top: 1px solid var(--makro-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .total-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--makro-red);
        }

        .empty-orders {
            text-align: center;
            padding: 60px 20px;
            color: var(--makro-gray);
        }

        .empty-orders i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--makro-red);
            opacity: 0.7;
        }

        .btn-select-product {
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            max-width: 200px;
            justify-content: center;
        }

        .btn-select-product:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .item-card {
                flex-direction: column;
                text-align: center;
            }
            
            .order-summary {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="page-header">
    <div class="container">
        <div class="page-header-content">
            <h1 class="page-title">
                <i class="fas fa-list-alt"></i> ประวัติการสั่งซื้อ
            </h1>
            <p class="page-subtitle">ดูรายการคำสั่งซื้อและสถานะการจัดส่ง</p>
        </div>
    </div>
</div>

<div class="container">
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo ($_SESSION['message_type'] ?? 'info'); ?> alert-dismissible fade show fade-in-up" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty-orders fade-in-up">
            <i class="fas fa-shopping-bag"></i>
            <h3>ยังไม่มีคำสั่งซื้อ</h3>
            <p>คุณยังไม่ได้สั่งซื้อสินค้าใดๆ</p>
            <a href="product-list1.php" class="btn btn-primary btn-select-product">
                <i class="fas fa-shopping-cart"></i> เลือกสินค้า
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card fade-in-up">
                <div class="order-header">
                    <div class="order-info">
                        <div class="order-number">
                            คำสั่งซื้อ #<?php echo $order['id']; ?>
                        </div>
                        <div class="order-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="order-status">
                        <?php echo getStatusBadge($order['status']); ?>
                    </div>
                    
                    <?php if ($order['status'] === 'pending'): ?>
                        <div class="order-actions">
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="cancel_order" class="btn btn-cancel" 
                                        onclick="return confirm('คุณต้องการยกเลิกคำสั่งซื้อนี้หรือไม่?')">
                                    <i class="fas fa-times"></i> ยกเลิก
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="order-items">
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="item-card">
                            <div class="item-image">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="upload_image/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <?php else: ?>
                                    <img src="https://placehold.co/60x60/cccccc/333333?text=No+Image" 
                                         alt="No Image">
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="item-price">฿<?php echo number_format($item['price'], 2); ?></div>
                                <div class="item-quantity">จำนวน: <?php echo $item['quantity']; ?> ชิ้น</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-summary">
                    <div>
                        <strong>จำนวนสินค้า:</strong> <?php echo $order['item_count']; ?> ชิ้น
                    </div>
                    <div class="total-amount">
                        ยอดรวม: ฿<?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
