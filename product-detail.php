<?php
session_start();
include 'config.php';

$product = null;
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // ใช้ฟังก์ชันจาก config.php
    $conn = getConnection();
    $query_str = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = $1";
    
    $result = pg_query_params($conn, $query_str, [$product_id]);
    
    if ($result && pg_num_rows($result) > 0) {
        $product = pg_fetch_assoc($result);
        
        // บันทึก Activity Log
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'view', 'User viewed product: ' . $product['name'], 'products', $product_id);
        }
    } else {
        $_SESSION['message'] = "ไม่พบสินค้าที่ระบุ!";
        $_SESSION['message_type'] = "error";
        header("Location: product-list1.php");
        exit();
    }
    
    // ไม่ต้องปิดการเชื่อมต่อเพราะ logActivity() ปิดแล้ว
} else {
    $_SESSION['message'] = "รหัสสินค้าไม่ถูกต้อง!";
    $_SESSION['message_type'] = "error";
    header("Location: product-list1.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }
        

        
        .container {
            padding-top: 30px;
            padding-bottom: 50px;
        }
        
        .product-detail-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(220, 53, 69, 0.1);
            padding: 40px;
            border-top: 4px solid #dc3545;
        }
        
        .product-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .product-description {
            font-size: 1.1rem;
            color: #6c757d;
            line-height: 1.8;
            margin-bottom: 30px;
        }
        
        .product-category {
            display: inline-block;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 500;
            margin-bottom: 30px;
        }
        
        .back-btn {
            background: #6c757d;
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
        }
        
        .add-to-cart-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 25px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.4);
            color: white;
        }
        
        .stock-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .stock-available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stock-low {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .stock-out {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        

    </style>
</head>
<body>

<?php include 'include/menu.php'; ?>

<div class="container">
    <div class="product-detail-card fade-in">
        <div class="row">
            <div class="col-md-6 text-center">
                <?php 
                $image_path = '';
                if (!empty($product['image_url'])) {
                    if (strpos($product['image_url'], '[1]') !== false) {
                        $image_path = 'https://placehold.co/400x400/cccccc/333333?text=Image+Missing';
                    } else {
                        $image_path = 'upload_image/' . htmlspecialchars($product['image_url']);
                    }
                } else {
                    $image_path = 'https://placehold.co/400x400/cccccc/333333?text=No+Image';
                }
                ?>
                <img src="<?php echo $image_path; ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-start">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <a href="product-list1.php" class="btn back-btn">
                        <i class="fas fa-arrow-left me-2"></i>กลับ
                    </a>
                </div>
                
                <div class="product-category">
                    <i class="fas fa-tag me-2"></i>
                    <?php echo htmlspecialchars($product['category_name'] ?: 'ไม่ระบุหมวดหมู่'); ?>
                </div>
                
                <div class="product-price">
                    ฿<?php echo number_format($product['price'], 2); ?>
                </div>
                
                <div class="stock-info">
                    <h6><i class="fas fa-boxes"></i> สถานะสินค้า:</h6>
                    <span class="stock-badge <?php echo $product['current_stock'] > 10 ? 'stock-available' : ($product['current_stock'] > 0 ? 'stock-low' : 'stock-out'); ?>">
                        <i class="fas fa-boxes"></i> 
                        <?php if ($product['current_stock'] > 10): ?>
                            มีสินค้า (<?php echo $product['current_stock']; ?> ชิ้น)
                        <?php elseif ($product['current_stock'] > 0): ?>
                            สินค้าใกล้หมด (<?php echo $product['current_stock']; ?> ชิ้น)
                        <?php else: ?>
                            สินค้าหมด
                        <?php endif; ?>
                    </span>
                </div>
                
                <p class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?: 'ไม่มีรายละเอียดสินค้า')); ?>
                </p>
                
                <?php if ($product['current_stock'] > 0): ?>
                    <a href="cart-add.php?id=<?php echo $product['id']; ?>" class="btn add-to-cart-btn">
                        <i class="fas fa-shopping-cart me-2"></i>เพิ่มลงตะกร้า
                    </a>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-times-circle me-2"></i>สินค้าหมด
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
