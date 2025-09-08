<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: user-login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// จัดการการเพิ่มสินค้าเข้ารายการโปรด
if (isset($_GET['add']) && isset($_GET['id'])) {
    $product_id = $_GET['id'];
    if (addToWishlist($user_id, $product_id)) {
        header("Location: wishlist.php?success=added");
        exit();
    } else {
        $error = "เกิดข้อผิดพลาดในการเพิ่มสินค้าเข้ารายการโปรด";
    }
}

// จัดการการลบสินค้าออกจากรายการโปรด
if (isset($_GET['remove']) && isset($_GET['id'])) {
    $product_id = $_GET['id'];
    if (removeFromWishlist($user_id, $product_id)) {
        header("Location: wishlist.php?success=removed");
        exit();
    } else {
        $error = "เกิดข้อผิดพลาดในการลบสินค้าออกจากรายการโปรด";
    }
}

// ดึงรายการสินค้าในรายการโปรด
$wishlist_items = getWishlistItems($user_id);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการโปรด - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #fd7e14;
            --accent-color: #ffc107;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: var(--light-color);
        }

        .wishlist-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            text-align: center;
        }

        .wishlist-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }

        .wishlist-item {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
        }

        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(220, 53, 69, 0.15);
            border-color: var(--primary-color);
        }

        .wishlist-item img {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }

        .item-details h5 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .item-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .item-category {
            background: linear-gradient(135deg, var(--info-color), var(--primary-color));
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }

        .item-stock {
            margin-bottom: 20px;
        }

        .stock-available {
            background: var(--success-color);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .stock-low {
            background: var(--accent-color);
            color: var(--dark-color);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .stock-out {
            background: var(--primary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .item-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            text-decoration: none;
        }

        .btn-danger {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success-color);
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .remove-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(220, 53, 69, 0.1);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            color: var(--primary-color);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }

        .empty-wishlist {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-wishlist i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        .success-message {
            background: var(--success-color);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .error-message {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .wishlist-stats {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stats-label {
            color: #6c757d;
            font-size: 1rem;
        }

        .quick-actions {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .btn-lg {
            padding: 15px 30px;
            font-size: 1.1rem;
            margin: 0 10px;
        }

        @media (max-width: 768px) {
            .wishlist-container {
                padding: 20px;
            }
            
            .wishlist-item {
                padding: 20px;
            }
            
            .item-actions {
                flex-direction: column;
            }
            
            .btn-lg {
                margin: 5px 0;
                width: 100%;
            }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="wishlist-header">
    <div class="container">
        <h1><i class="fas fa-heart"></i> รายการโปรด</h1>
        <p class="mb-0">สินค้าที่คุณชื่นชอบและต้องการเก็บไว้ดูภายหลัง</p>
    </div>
</div>

<div class="container">
    <!-- ข้อความแจ้งเตือน -->
    <?php if (isset($_GET['success'])): ?>
        <?php if ($_GET['success'] === 'added'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> เพิ่มสินค้าเข้ารายการโปรดสำเร็จแล้ว!
            </div>
        <?php elseif ($_GET['success'] === 'removed'): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> ลบสินค้าออกจากรายการโปรดสำเร็จแล้ว!
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- สถิติรายการโปรด -->
    <div class="wishlist-stats">
        <div class="row">
            <div class="col-md-4">
                <div class="stats-number"><?php echo count($wishlist_items); ?></div>
                <div class="stats-label">สินค้าในรายการโปรด</div>
            </div>
            <div class="col-md-4">
                <div class="stats-number">
                    <?php 
                    $total_value = 0;
                    foreach ($wishlist_items as $item) {
                        $total_value += $item['price'];
                    }
                    echo '฿' . number_format($total_value, 2);
                    ?>
                </div>
                <div class="stats-label">มูลค่ารวม</div>
            </div>
            <div class="col-md-4">
                <div class="stats-number">
                    <?php 
                    $available_count = 0;
                    foreach ($wishlist_items as $item) {
                        if ($item['current_stock'] > 0) {
                            $available_count++;
                        }
                    }
                    echo $available_count;
                    ?>
                </div>
                <div class="stats-label">สินค้าที่มีสต็อก</div>
            </div>
        </div>
    </div>

    <!-- การกระทำด่วน -->
    <div class="quick-actions">
        <h4 class="mb-3">การกระทำด่วน</h4>
        <a href="product-list1.php" class="btn btn-outline-primary btn-lg">
            <i class="fas fa-boxes"></i> ดูสินค้าทั้งหมด
        </a>
        <a href="cart.php" class="btn btn-success btn-lg">
            <i class="fas fa-shopping-cart"></i> ดูตะกร้าสินค้า
        </a>
        <a href="product_comparison.php" class="btn btn-info btn-lg">
            <i class="fas fa-balance-scale"></i> เปรียบเทียบสินค้า
        </a>
    </div>

    <?php if (empty($wishlist_items)): ?>
        <!-- รายการโปรดว่าง -->
        <div class="wishlist-container">
            <div class="empty-wishlist">
                <i class="fas fa-heart-broken"></i>
                <h3>รายการโปรดของคุณว่างเปล่า</h3>
                <p>ยังไม่มีสินค้าในรายการโปรด</p>
                <a href="product-list1.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-boxes"></i> เลือกสินค้า
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- รายการสินค้าในรายการโปรด -->
        <div class="wishlist-container">
            <h3 class="mb-4"><i class="fas fa-heart"></i> สินค้าในรายการโปรด (<?php echo count($wishlist_items); ?>)</h3>
            
            <div class="row">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="col-lg-6 mb-4 fade-in">
                        <div class="wishlist-item">
                            <!-- ปุ่มลบ -->
                            <a href="wishlist.php?remove=1&id=<?php echo $item['id']; ?>" 
                               class="remove-btn" 
                               onclick="return confirm('ลบสินค้านี้ออกจากรายการโปรด?')"
                               title="ลบออกจากรายการโปรด">
                                <i class="fas fa-times"></i>
                            </a>

                            <div class="row">
                                <div class="col-md-4 text-center">
                                    <img src="<?php echo !empty($item['image_url']) ? 'upload_image/' . htmlspecialchars($item['image_url']) : 'https://placehold.co/120x120/cccccc/333333?text=No+Image'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid">
                                </div>
                                <div class="col-md-8">
                                    <div class="item-details">
                                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <div class="item-price">฿<?php echo number_format($item['price'], 2); ?></div>
                                        <div class="item-category">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($item['category_name'] ?: 'ไม่ระบุ'); ?>
                                        </div>
                                        
                                        <div class="item-stock">
                                            <?php if ($item['current_stock'] > 10): ?>
                                                <span class="stock-available">
                                                    <i class="fas fa-check-circle"></i> มีสินค้า (<?php echo $item['current_stock']; ?>)
                                                </span>
                                            <?php elseif ($item['current_stock'] > 0): ?>
                                                <span class="stock-low">
                                                    <i class="fas fa-exclamation-triangle"></i> สินค้าใกล้หมด (<?php echo $item['current_stock']; ?>)
                                                </span>
                                            <?php else: ?>
                                                <span class="stock-out">
                                                    <i class="fas fa-times-circle"></i> สินค้าหมด
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="item-actions">
                                            <a href="product-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i> ดูรายละเอียด
                                            </a>
                                            
                                            <?php if ($item['current_stock'] > 0): ?>
                                                <a href="cart-add.php?id=<?php echo $item['id']; ?>" class="btn btn-success">
                                                    <i class="fas fa-shopping-cart"></i> เพิ่มลงตะกร้า
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-times"></i> สินค้าหมด
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // เพิ่ม animation เมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function() {
        const items = document.querySelectorAll('.fade-in');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // ยืนยันการลบสินค้า
    function confirmRemove(productName) {
        return confirm(`คุณต้องการลบ "${productName}" ออกจากรายการโปรดหรือไม่?`);
    }

    // Auto-hide success messages
    setTimeout(function() {
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(message => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease';
            setTimeout(() => message.remove(), 500);
        });
    }, 3000);
</script>
</body>
</html>
