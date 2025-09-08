<?php
session_start();
include 'config.php';

// ดึงสินค้าที่เลือกมาเปรียบเทียบ
$compare_ids = isset($_GET['compare']) ? explode(',', $_GET['compare']) : [];
$compare_ids = array_filter($compare_ids); // ลบค่าว่าง

// จำกัดการเปรียบเทียบไม่เกิน 4 สินค้า
if (count($compare_ids) > 4) {
    $compare_ids = array_slice($compare_ids, 0, 4);
}

// ดึงข้อมูลสินค้าที่จะเปรียบเทียบ
$products_to_compare = [];
if (!empty($compare_ids)) {
    foreach ($compare_ids as $id) {
        $product = getProductById($id);
        if ($product) {
            $products_to_compare[] = $product;
        }
    }
}

// จัดการการเพิ่มสินค้าเข้าเปรียบเทียบ
if (isset($_GET['add']) && isset($_SESSION['user_id'])) {
    $add_id = $_GET['add'];
    if (!in_array($add_id, $compare_ids)) {
        $compare_ids[] = $add_id;
        if (count($compare_ids) > 4) {
            array_shift($compare_ids); // ลบสินค้าแรกออก
        }
    }
    header("Location: product_comparison.php?compare=" . implode(',', $compare_ids));
    exit();
}

// จัดการการลบสินค้าออกจากเปรียบเทียบ
if (isset($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    $compare_ids = array_filter($compare_ids, function($id) use ($remove_id) {
        return $id != $remove_id;
    });
    header("Location: product_comparison.php?compare=" . implode(',', $compare_ids));
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปรียบเทียบสินค้า - ZapShop</title>
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

        .comparison-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            text-align: center;
        }

        .comparison-table {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .comparison-table table {
            margin: 0;
        }

        .comparison-table th {
            background: var(--light-color);
            border: none;
            padding: 20px 15px;
            font-weight: 600;
            color: var(--dark-color);
            text-align: center;
            vertical-align: middle;
        }

        .comparison-table td {
            border: 1px solid #e9ecef;
            padding: 20px 15px;
            text-align: center;
            vertical-align: middle;
        }

        .product-column {
            background: white;
            min-width: 200px;
        }

        .product-image {
            width: 120px;
            height: 120px;
            border-radius: 15px;
            object-fit: cover;
            margin: 0 auto 15px;
            display: block;
            border: 3px solid var(--primary-color);
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .btn-compare {
            background: var(--primary-color);
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .btn-compare:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-remove {
            background: var(--dark-color);
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-remove:hover {
            background: #495057;
            transform: translateY(-1px);
        }

        .feature-row {
            background: var(--light-color);
        }

        .feature-label {
            font-weight: 600;
            color: var(--dark-color);
            text-align: left;
            padding-left: 20px;
        }

        .feature-value {
            color: var(--dark-color);
            font-weight: 500;
        }

        .empty-column {
            background: #f8f9fa;
            color: #6c757d;
            font-style: italic;
        }

        .add-product-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            text-align: center;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.15);
            border-color: var(--primary-color);
        }

        .product-card img {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
            margin: 0 auto 15px;
            display: block;
        }

        .product-card h5 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .product-card .price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .btn-add-compare {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-add-compare:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            color: white;
        }

        .btn-add-compare:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }

        .comparison-actions {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }

        .btn-clear-all {
            background: var(--dark-color);
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .btn-clear-all:hover {
            background: #495057;
            transform: translateY(-2px);
        }

        .btn-share {
            background: var(--info-color);
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-share:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-products i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        @media (max-width: 768px) {
            .comparison-table {
                overflow-x: auto;
            }
            
            .comparison-table th,
            .comparison-table td {
                min-width: 150px;
                padding: 15px 10px;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="comparison-header">
    <div class="container">
        <h1><i class="fas fa-balance-scale"></i> เปรียบเทียบสินค้า</h1>
        <p class="mb-0">เปรียบเทียบสินค้าที่คุณสนใจเพื่อตัดสินใจซื้อ</p>
    </div>
</div>

<div class="container">
    <?php if (empty($products_to_compare)): ?>
        <!-- ไม่มีสินค้าเปรียบเทียบ -->
        <div class="no-products">
            <i class="fas fa-balance-scale"></i>
            <h3>ยังไม่มีสินค้าเปรียบเทียบ</h3>
            <p>เลือกสินค้าที่คุณต้องการเปรียบเทียบ</p>
            <a href="product-list1.php" class="btn btn-primary btn-lg">
                <i class="fas fa-boxes"></i> ดูสินค้าทั้งหมด
            </a>
        </div>
    <?php else: ?>
        <!-- การกระทำหลัก -->
        <div class="comparison-actions">
            <a href="product_comparison.php" class="btn btn-clear-all">
                <i class="fas fa-trash"></i> ล้างทั้งหมด
            </a>
            <button class="btn btn-share" onclick="shareComparison()">
                <i class="fas fa-share"></i> แชร์การเปรียบเทียบ
            </button>
        </div>

        <!-- ตารางเปรียบเทียบ -->
        <div class="comparison-table">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th style="width: 200px;">คุณสมบัติ</th>
                        <?php foreach ($products_to_compare as $product): ?>
                            <th class="product-column">
                                <img src="<?php echo !empty($product['image_url']) ? 'upload_image/' . htmlspecialchars($product['image_url']) : 'https://placehold.co/120x120/cccccc/333333?text=No+Image'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-price">฿<?php echo number_format($product['price'], 2); ?></div>
                                <div class="product-actions">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn-compare">
                                        <i class="fas fa-eye"></i> ดูรายละเอียด
                                    </a>
                                    <a href="product_comparison.php?remove=<?php echo $product['id']; ?>&compare=<?php echo implode(',', $compare_ids); ?>" 
                                       class="btn-remove" onclick="return confirm('ลบสินค้านี้ออกจากเปรียบเทียบ?')">
                                        <i class="fas fa-times"></i> ลบ
                                    </a>
                                </div>
                            </th>
                        <?php endforeach; ?>
                        <?php for ($i = count($products_to_compare); $i < 4; $i++): ?>
                            <th class="empty-column">
                                <i class="fas fa-plus fa-2x mb-2"></i>
                                <div>เพิ่มสินค้า</div>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- ราคา -->
                    <tr class="feature-row">
                        <th class="feature-label">ราคา</th>
                        <?php foreach ($products_to_compare as $product): ?>
                            <td class="feature-value">฿<?php echo number_format($product['price'], 2); ?></td>
                        <?php endforeach; ?>
                        <?php for ($i = count($products_to_compare); $i < 4; $i++): ?>
                            <td class="empty-column">-</td>
                        <?php endfor; ?>
                    </tr>

                    <!-- หมวดหมู่ -->
                    <tr>
                        <th class="feature-label">หมวดหมู่</th>
                        <?php foreach ($products_to_compare as $product): ?>
                            <td class="feature-value"><?php echo htmlspecialchars($product['category_name'] ?: 'ไม่ระบุ'); ?></td>
                        <?php endforeach; ?>
                        <?php for ($i = count($products_to_compare); $i < 4; $i++): ?>
                            <td class="empty-column">-</td>
                        <?php endfor; ?>
                    </tr>

                    <!-- สต็อก -->
                    <tr class="feature-row">
                        <th class="feature-label">สต็อก</th>
                        <?php foreach ($products_to_compare as $product): ?>
                            <td class="feature-value">
                                <?php if ($product['current_stock'] > 10): ?>
                                    <span class="badge bg-success">มีสินค้า (<?php echo $product['current_stock']; ?>)</span>
                                <?php elseif ($product['current_stock'] > 0): ?>
                                    <span class="badge bg-warning">ใกล้หมด (<?php echo $product['current_stock']; ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">สินค้าหมด</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php for ($i = count($products_to_compare); $i < 4; $i++): ?>
                            <td class="empty-column">-</td>
                        <?php endfor; ?>
                    </tr>

                    <!-- รายละเอียด -->
                    <tr>
                        <th class="feature-label">รายละเอียด</th>
                        <?php foreach ($products_to_compare as $product): ?>
                            <td class="feature-value">
                                <?php echo htmlspecialchars(substr($product['description'] ?: 'ไม่มีรายละเอียด', 0, 100)); ?>
                                <?php if (strlen($product['description'] ?: '') > 100): ?>...<?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php for ($i = count($products_to_compare); $i < 4; $i++): ?>
                            <td class="empty-column">-</td>
                        <?php endfor; ?>
                    </tr>

                    <!-- การกระทำ -->
                    <tr class="feature-row">
                        <th class="feature-label">การกระทำ</th>
                        <?php foreach ($products_to_compare as $product): ?>
                            <td class="feature-value">
                                <div class="d-grid gap-2">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> ดูรายละเอียด
                                    </a>
                                    <?php if (isset($_SESSION['user_id']) && $product['current_stock'] > 0): ?>
                                        <a href="cart-add.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="fas fa-shopping-cart"></i> เพิ่มลงตะกร้า
                                        </a>
                                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                                        <a href="user-login.php" class="btn btn-sm btn-warning">
                                            <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-times"></i> สินค้าหมด
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                        <?php for ($i = count($products_to_compare); $i < 4; $i++): ?>
                            <td class="empty-column">-</td>
                        <?php endfor; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ส่วนเพิ่มสินค้าเปรียบเทียบ -->
    <div class="add-product-section">
        <h3><i class="fas fa-plus-circle"></i> เพิ่มสินค้าเปรียบเทียบ</h3>
        <p class="text-muted">เลือกสินค้าเพิ่มเติมเพื่อเปรียบเทียบ (สูงสุด 4 สินค้า)</p>
        
        <div class="product-grid">
            <?php
            // ดึงสินค้าทั้งหมดที่ไม่ซ้ำกับที่เปรียบเทียบอยู่แล้ว
            $all_products = getAllProducts();
            $available_products = array_filter($all_products, function($product) use ($compare_ids) {
                return !in_array($product['id'], $compare_ids);
            });
            
            // แสดงเฉพาะ 8 สินค้าแรก
            $available_products = array_slice($available_products, 0, 8);
            ?>
            
            <?php foreach ($available_products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo !empty($product['image_url']) ? 'upload_image/' . htmlspecialchars($product['image_url']) : 'https://placehold.co/100x100/cccccc/333333?text=No+Image'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                    <div class="price">฿<?php echo number_format($product['price'], 2); ?></div>
                    <a href="product_comparison.php?add=<?php echo $product['id']; ?>&compare=<?php echo implode(',', $compare_ids); ?>" 
                       class="btn-add-compare">
                        <i class="fas fa-plus"></i> เพิ่มเปรียบเทียบ
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($available_products)): ?>
            <div class="text-center py-4">
                <p class="text-muted">ไม่มีสินค้าเพิ่มเติมให้เปรียบเทียบ</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    // ฟังก์ชันแชร์การเปรียบเทียบ
    function shareComparison() {
        const currentUrl = window.location.href;
        
        if (navigator.share) {
            navigator.share({
                title: 'เปรียบเทียบสินค้า - ZapShop',
                text: 'ดูการเปรียบเทียบสินค้าที่น่าสนใจ',
                url: currentUrl
            });
        } else {
            // Fallback สำหรับเบราว์เซอร์ที่ไม่รองรับ Web Share API
            navigator.clipboard.writeText(currentUrl).then(() => {
                alert('คัดลอกลิงก์การเปรียบเทียบแล้ว!');
            });
        }
    }

    // เพิ่ม animation เมื่อโหลดหน้า
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.product-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
</body>
</html>
