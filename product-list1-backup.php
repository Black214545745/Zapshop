<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}
include 'shared_config.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// ดึงข้อมูลหมวดหมู่
$categories = getCategories();

$search_query = "";
$selected_category_id = "";

if (isset($_POST['search'])) {
    $search_query = trim($_POST['search']);
}

if (isset($_GET['category_id']) && $_GET['category_id'] != "") {
    $selected_category_id = $_GET['category_id'];
} elseif (isset($_POST['category_id']) && $_POST['category_id'] != "") {
    $selected_category_id = $_POST['category_id'];
}

// ดึงข้อมูลสินค้า
$products = [];
$rows = 0;

try {
    $products = getProducts(1, 100, $selected_category_id, $search_query);
    $rows = count($products);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = "เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล!";
    $_SESSION['message_type'] = "error";
}

// บันทึก Activity Log (ถ้ามีฟังก์ชัน)
if (isset($_SESSION['user_id']) && function_exists('logActivity')) {
    logActivity($_SESSION['user_id'], 'view_products', 'User viewed product list', 'products');
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้า - ZapShop</title>
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
            --makro-white: #ffffff;
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

        .main-content {
            padding: 40px 0;
        }

        /* Page Header - สไตล์ Makro */
        .page-header {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            color: white;
            padding: 50px 0;
            margin-bottom: 40px;
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
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Search Section - สไตล์ Makro */
        .search-section {
            background: var(--makro-white);
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 30px;
            margin-bottom: 30px;
            border-top: 4px solid;
            background: linear-gradient(90deg, var(--makro-red), var(--makro-orange), var(--makro-yellow));
        }

        .search-title {
            color: var(--makro-red);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: var(--makro-radius-sm);
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--makro-white);
        }

        .form-control:focus {
            border-color: var(--makro-red);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
            outline: none;
        }

        .form-select {
            border: 2px solid #e1e5e9;
            border-radius: var(--makro-radius-sm);
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--makro-white);
        }

        .form-select:focus {
            border-color: var(--makro-red);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
            outline: none;
        }

        .btn-search {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            border: none;
            border-radius: var(--makro-radius-sm);
            padding: 12px 30px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-search:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(227, 24, 55, 0.3);
            color: white;
        }

        .btn-search:hover::before {
            left: 100%;
        }

        /* Results Info - สไตล์ Makro */
        .results-info {
            background: var(--makro-white);
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 20px 25px;
            margin-bottom: 30px;
            border-left: 4px solid var(--makro-red);
            transition: transform 0.3s ease;
        }

        .results-info:hover {
            transform: translateY(-2px);
            box-shadow: var(--makro-shadow-hover);
        }

        .results-title {
            color: var(--makro-red);
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .results-count {
            background: var(--makro-red);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Product Grid - สไตล์ Makro */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: var(--makro-white);
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--makro-border);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--makro-shadow-hover);
        }

        .product-image-container {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--makro-red);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }

        .product-info {
            padding: 25px;
        }

        .product-title {
            font-weight: 700;
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 15px;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-category {
            color: var(--makro-red);
            font-size: 0.9rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .product-category i {
            font-size: 0.8rem;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--makro-red);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .price-currency {
            font-size: 1rem;
            color: var(--makro-gray);
        }

        .product-stock {
            margin-bottom: 20px;
        }

        .stock-label {
            font-size: 0.9rem;
            color: var(--makro-gray);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .stock-in {
            background: #d4edda;
            color: #155724;
        }

        .stock-low {
            background: #fff3cd;
            color: #856404;
        }

        .stock-out {
            background: #f8d7da;
            color: #721c24;
        }

        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 20px;
        }

        .btn-detail {
            background: var(--makro-gray);
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            color: var(--makro-white);
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            cursor: pointer;
        }

        .btn-detail:hover {
            background: var(--makro-blue);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.3);
            color: var(--makro-white);
            text-decoration: none;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, var(--makro-red) 0%, var(--makro-red-dark) 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            color: var(--makro-white);
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            width: 100%;
            cursor: pointer;
        }

        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(227, 24, 55, 0.4);
            color: var(--makro-white);
            text-decoration: none;
        }

        .btn-add-cart:disabled {
            background: var(--makro-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Cart Controls - สไตล์ Makro */
        .cart-controls {
            display: flex;
            flex-direction: column;
            gap: 8px;
            width: 100%;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--makro-light-gray);
            padding: 8px;
            border-radius: 8px;
            border: 1px solid var(--makro-border);
            margin-bottom: 8px;
        }

        .quantity-btn {
            background: var(--makro-red);
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .quantity-btn:hover {
            background: var(--makro-red-dark);
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(227, 24, 55, 0.3);
        }

        .quantity-btn:active {
            transform: scale(0.95);
        }

        .quantity-btn:disabled {
            background: var(--makro-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid var(--makro-border);
            border-radius: 6px;
            padding: 6px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
            background: white;
        }

        .quantity-input:focus {
            outline: none;
            border-color: var(--makro-red);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
        }

        .quantity-input::-webkit-inner-spin-button,
        .quantity-input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .quantity-input[type=number] {
            -moz-appearance: textfield;
        }

        /* Responsive adjustments for quantity controls */
        @media (max-width: 768px) {
            .cart-controls {
                gap: 10px;
            }
            
            .quantity-selector {
                padding: 7px;
                gap: 7px;
            }
            
            .quantity-btn {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }
            
            .quantity-input {
                width: 48px;
                padding: 6px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .cart-controls {
                gap: 10px;
            }
            
            .quantity-selector {
                padding: 6px;
                gap: 6px;
            }
            
            .quantity-btn {
                width: 26px;
                height: 26px;
                font-size: 0.7rem;
            }
            
            .quantity-input {
                width: 45px;
                padding: 5px;
                font-size: 0.8rem;
            }
        }

        /* Empty State - สไตล์ Makro */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--makro-white);
            border-radius: 20px;
            box-shadow: var(--makro-shadow);
        }

        .empty-icon {
            font-size: 5rem;
            color: var(--makro-gray);
            margin-bottom: 20px;
        }

        .empty-title {
            color: var(--makro-blue);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .empty-description {
            color: var(--makro-gray);
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 0;
            }

            .page-header {
                padding: 20px 0;
                margin-bottom: 30px;
            }

            .page-title {
                font-size: 2rem;
            }

            .search-section {
                padding: 25px;
                margin-bottom: 30px;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }

            .results-info {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .search-section {
                padding: 20px;
            }

            .product-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .product-actions {
                flex-direction: column;
            }

            .page-title {
                font-size: 1.8rem;
            }
        }

        /* Animation */
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

        .stagger-animation {
            animation: staggerFadeIn 0.8s ease-out forwards;
        }

        @keyframes staggerFadeIn {
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

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="page-header-content">
            <h1 class="page-title">🛍️ รายการสินค้าทั้งหมด</h1>
            <p class="page-subtitle">ค้นหาและเลือกซื้อสินค้าที่คุณต้องการได้ที่นี่</p>
        </div>
    </div>
</div>

<div class="container main-content">
    <?php if (!empty($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo ($_SESSION['message_type'] ?? 'info'); ?> alert-dismissible fade show fade-in-up" role="alert">
            <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Search Section -->
    <div class="search-section fade-in-up">
        <h4 class="search-title">
            <i class="fas fa-search"></i>
            ค้นหาสินค้า
        </h4>
        
        <form method="POST" action="product-list1.php" id="searchForm">
            <div class="row g-3">
                <div class="col-lg-5 col-md-6">
                    <input type="text" class="form-control" name="search" id="searchInput"
                           placeholder="🔍 ค้นหาสินค้า..." 
                           value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <div class="col-lg-4 col-md-6">
                    <select class="form-select" name="category_id" id="categorySelect">
                        <option value="">📂 ทุกหมวดหมู่</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($selected_category_id == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-12">
                    <button type="submit" class="btn btn-search w-100" id="searchBtn">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Results Info -->
    <div class="results-info fade-in-up">
        <div class="d-flex align-items-center gap-3">
            <h5 class="results-title">
                <i class="fas fa-boxes"></i>
                รายการสินค้า
            </h5>
            <span class="results-count"><?php echo $rows; ?> รายการ</span>
        </div>
    </div>
    
    <!-- Products Grid -->
    <?php if (empty($products)): ?>
        <div class="empty-state fade-in-up">
            <div class="empty-icon">📦</div>
            <h4 class="empty-title">ไม่พบสินค้า</h4>
            <p class="empty-description">ลองเปลี่ยนคำค้นหาหรือหมวดหมู่เพื่อหาสินค้าที่ต้องการ</p>
            <a href="product-list1.php" class="btn btn-search">
                <i class="fas fa-refresh"></i> ดูสินค้าทั้งหมด
            </a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $index => $product): ?>
                <div class="product-card stagger-animation" style="animation-delay: <?php echo $index * 0.1; ?>s;">
                    <div class="product-image-container">
                        <?php if (!empty($product['profile_image'])): ?>
                            <img src="<?php echo htmlspecialchars($product['profile_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <span style="font-size: 3rem; color: #ccc;">📦</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-badge">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'ไม่มีหมวดหมู่'); ?>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <h6 class="product-title">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </h6>
                        
                        <div class="product-category">
                            <i class="fas fa-tag"></i>
                            <?php echo htmlspecialchars($product['category_name'] ?? 'ไม่มีหมวดหมู่'); ?>
                        </div>
                        
                        <div class="product-price">
                            <span class="price-currency">฿</span>
                            <?php echo number_format($product['price'], 2); ?>
                        </div>
                        
                        <div class="product-stock">
                            <div class="stock-label">
                                <i class="fas fa-cubes"></i> สถานะสต็อก:
                            </div>
                            <?php 
                            $stock = $product['current_stock'];
                            if ($stock > 10) {
                                echo '<span class="stock-status stock-in"><i class="fas fa-check-circle"></i> ' . $stock . ' ชิ้น</span>';
                            } elseif ($stock > 0) {
                                echo '<span class="stock-status stock-low"><i class="fas fa-exclamation-triangle"></i> ' . $stock . ' ชิ้น</span>';
                            } else {
                                echo '<span class="stock-status stock-out"><i class="fas fa-times-circle"></i> หมด</span>';
                            }
                            ?>
                        </div>
                        
                        <div class="product-actions">
                            <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-detail">
                                <i class="fas fa-eye"></i> ดูรายละเอียด
                            </a>
                            <?php if (isset($_SESSION['user_id']) && $product['current_stock'] > 0): ?>
                                <div class="cart-controls" id="cart-controls-<?php echo $product['id']; ?>">
                                    <!-- Quantity Controls (แสดงพร้อมกับปุ่มเพิ่มลงตะกร้า) -->
                                    <div class="quantity-selector">
                                        <button class="quantity-btn minus" onclick="changeQuantity(<?php echo $product['id']; ?>, -1)" title="ลดจำนวน">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="quantity-input" id="qty-<?php echo $product['id']; ?>" 
                                               value="1" min="1" max="<?php echo $product['current_stock']; ?>" 
                                               onchange="updateQuantity(<?php echo $product['id']; ?>, this.value)">
                                        <button class="quantity-btn plus" onclick="changeQuantity(<?php echo $product['id']; ?>, 1)" title="เพิ่มจำนวน">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- ปุ่มเพิ่มลงตะกร้า -->
                                    <button class="btn btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i> เพิ่มลงตะกร้า
                                    </button>
                                </div>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="user-login.php" class="btn btn-add-cart">
                                    <i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ
                                </a>
                            <?php else: ?>
                                <button class="btn btn-add-cart" disabled>
                                    <i class="fas fa-times-circle"></i> สินค้าหมด
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
// เพิ่ม animation เมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม class สำหรับ animation
    const elements = document.querySelectorAll('.fade-in-up, .stagger-animation');
    elements.forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(20px)';
    });

    // เริ่ม animation
    setTimeout(() => {
        elements.forEach((element, index) => {
            setTimeout(() => {
                element.style.transition = 'all 0.6s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }, 300);
});

// เพิ่มการตรวจสอบการค้นหา
document.querySelector('form').addEventListener('submit', function(e) {
    const searchInput = document.querySelector('input[name="search"]');
    const categorySelect = document.querySelector('select[name="category_id"]');
    
    if (!searchInput.value.trim() && categorySelect.value === '') {
        e.preventDefault();
        alert('กรุณาเลือกคำค้นหาหรือหมวดหมู่');
        return false;
    }
});

// เพิ่ม hover effect สำหรับ product cards
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

    // ฟังก์ชันสำหรับการจัดการจำนวนสินค้า
    function changeQuantity(productId, change) {
        console.log('=== changeQuantity called ===');
        console.log('Product ID:', productId);
        console.log('Change:', change);
        
        const input = document.getElementById(`qty-${productId}`);
        console.log('Quantity input element:', input);
        
        if (!input) {
            console.error('❌ Quantity input not found for product:', productId);
            return;
        }
        
        const currentQty = parseInt(input.value) || 1;
        const maxQty = parseInt(input.max);
        const newQty = Math.max(1, Math.min(currentQty + change, maxQty));
        
        console.log('Quantity calculation:', { currentQty, change, newQty, maxQty });
        
        input.value = newQty;
        console.log('✅ Quantity updated to:', newQty);
        
        // อัปเดตสถานะปุ่ม
        updateButtonStates(productId);
    }

function updateQuantity(productId, newValue) {
    console.log('updateQuantity called for product:', productId, 'newValue:', newValue);
    
    const input = document.getElementById(`qty-${productId}`);
    if (!input) {
        console.error('Quantity input not found for product:', productId);
        return;
    }
    
    const maxQty = parseInt(input.max);
    const qty = Math.max(1, Math.min(parseInt(newValue) || 1, maxQty));
    
    console.log('Quantity updated:', { newValue, qty, maxQty });
    
    input.value = qty;
    
    // อัปเดตสถานะปุ่ม
    updateButtonStates(productId);
}

function updateButtonStates(productId) {
    const input = document.getElementById(`qty-${productId}`);
    if (!input) {
        console.error('Quantity input not found for product:', productId);
        return;
    }
    
    const minusBtn = input.parentElement.querySelector('.minus');
    const plusBtn = input.parentElement.querySelector('.plus');
    
    if (!minusBtn || !plusBtn) {
        console.error('Quantity buttons not found for product:', productId);
        return;
    }
    
    const currentQty = parseInt(input.value);
    const maxQty = parseInt(input.max);
    
    // ปิด/เปิดปุ่มตามจำนวน
    minusBtn.disabled = currentQty <= 1;
    plusBtn.disabled = currentQty >= maxQty;
    
    console.log('Button states updated for product:', productId, { currentQty, maxQty });
}

function updateQuantity(productId, newValue) {
    console.log('updateQuantity called for product:', productId, 'newValue:', newValue);
    
    const input = document.getElementById(`qty-${productId}`);
    if (!input) {
        console.error('Quantity input not found for product:', productId);
        return;
    }
    
    const maxQty = parseInt(input.max);
    const qty = Math.max(1, Math.min(parseInt(newValue) || 1, maxQty));
    
    console.log('Quantity updated:', { newValue, qty, maxQty });
    
    input.value = qty;
    
    // อัปเดตสถานะปุ่ม
    updateButtonStates(productId);
}

function updateButtonStates(productId) {
    const input = document.getElementById(`qty-${productId}`);
    if (!input) {
        console.error('Quantity input not found for product:', productId);
        return;
    }
    
    const minusBtn = input.parentElement.querySelector('.minus');
    const plusBtn = input.parentElement.querySelector('.plus');
    
    if (!minusBtn || !plusBtn) {
        console.error('Quantity buttons not found for product:', productId);
        return;
    }
    
    const currentQty = parseInt(input.value);
    const maxQty = parseInt(input.max);
    
    // ปิด/เปิดปุ่มตามจำนวน
    minusBtn.disabled = currentQty <= 1;
    plusBtn.disabled = currentQty >= maxQty;
    
    console.log('Button states updated for product:', productId, { currentQty, maxQty });
}

function addToCart(productId) {
    console.log('=== addToCart called ===');
    console.log('Product ID:', productId);
    
    const input = document.getElementById(`qty-${productId}`);
    console.log('Quantity input element:', input);
    
    if (!input) {
        console.error('❌ Quantity input not found for product:', productId);
        return;
    }
    
    const quantity = parseInt(input.value) || 1;
    console.log('Quantity to add:', quantity);
    
    // แสดง loading state
    const addBtn = input.parentElement.parentElement.querySelector('.btn-add-cart');
    console.log('Add button element:', addBtn);
    
    if (!addBtn) {
        console.error('❌ Add button not found for product:', productId);
        return;
    }
    
    const originalText = addBtn.innerHTML;
    console.log('Original button text:', originalText);
    
    addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังเพิ่ม...';
    addBtn.disabled = true;
    console.log('✅ Button set to loading state');
    
    // ส่งข้อมูลไปยัง cart-add.php
    console.log('🔄 Sending request to cart-add.php...');
    fetch('cart-add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${productId}&quantity=${quantity}`
    })
    .then(response => {
        console.log('📡 Response received:', response);
        return response.json();
    })
    .then(data => {
        console.log('📦 Response data:', data);
        if (data.success) {
            // แสดงข้อความสำเร็จ
            showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว!', 'success');
            
            // รีเซ็ตจำนวนเป็น 1
            input.value = 1;
            updateButtonStates(productId);
            
            // อัปเดตจำนวนสินค้าในตะกร้า
            updateCartCount();
            
            console.log('✅ Product added to cart successfully');
        } else {
            console.error('❌ Failed to add product:', data.message);
            showNotification(data.message || 'เกิดข้อผิดพลาดในการเพิ่มสินค้า', 'error');
        }
    })
    .catch(error => {
        console.error('❌ Error adding to cart:', error);
        showNotification('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    })
    .finally(() => {
        addBtn.innerHTML = originalText;
        addBtn.disabled = false;
        console.log('✅ Add to cart process completed');
    });
}

// ฟังก์ชันแสดงการแจ้งเตือน
function showNotification(message, type = 'info') {
    console.log('Showing notification:', { message, type });
    
    // สร้าง notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // ลบ notification หลังจาก 3 วินาที
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
    
    console.log('Notification displayed successfully');
}

// ฟังก์ชันอัปเดตจำนวนสินค้าในตะกร้า
function updateCartCount() {
    console.log('Updating cart count...');
    
    // อัปเดตจำนวนสินค้าในเมนู (ถ้ามี)
    const cartCountElement = document.querySelector('.cart-count');
    if (cartCountElement) {
        // ดึงข้อมูลตะกร้าจาก session หรือ API
        fetch('get-cart-count.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cartCountElement.textContent = data.count;
                    console.log('Cart count updated successfully:', data.count);
                } else {
                    console.error('Failed to update cart count:', data.message);
                }
            })
            .catch(error => {
                console.error('Error updating cart count:', error);
            });
    } else {
        console.log('Cart count element not found, skipping update');
    }
}

// อัปเดตสถานะปุ่มเมื่อโหลดหน้า
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing cart controls...');
    
    // อัปเดตสถานะปุ่มสำหรับทุกสินค้า
    const quantityInputs = document.querySelectorAll('.quantity-input');
    console.log('Found quantity inputs:', quantityInputs.length);
    
    quantityInputs.forEach(input => {
        const productId = input.id.replace('qty-', '');
        console.log('Initializing product:', productId);
        updateButtonStates(productId);
    });
    
        console.log('Cart controls initialization completed');
    });

    // Form validation สำหรับการค้นหา
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded, setting up form validation...');
        
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            console.log('Search form found, adding event listener...');
            searchForm.addEventListener('submit', function(e) {
                console.log('Form submit event triggered');
                const searchInput = document.getElementById('searchInput');
                const categorySelect = document.getElementById('categorySelect');
                
                console.log('Search input value:', searchInput ? searchInput.value : 'NOT FOUND');
                console.log('Category select value:', categorySelect ? categorySelect.value : 'NOT FOUND');
                
                // ตรวจสอบว่ามีการกรอกข้อมูลหรือไม่
                if ((!searchInput || !searchInput.value.trim()) && (!categorySelect || !categorySelect.value)) {
                    console.log('Form validation failed, preventing submit');
                    e.preventDefault();
                    alert('กรุณาเลือกคำค้นหาหรือหมวดหมู่');
                    return false;
                }
                
                console.log('Form validation passed, allowing submit');
            });
        } else {
            console.error('Search form not found!');
        }
    });
</script>
</body>
</html>
