<?php
session_start();
// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    // หากยังไม่ได้เข้าสู่ระบบ ให้รีไดเร็คไปที่หน้าเข้าสู่ระบบ
    header("Location: user-login.php");
    exit();
}
include 'config.php';

$productIds = [];
foreach(($_SESSION['cart'] ?? []) as $cartId => $cartQty) {
    $productIds[] = $cartId;
}

$products = [];
$total_amount = 0;
$total_items = 0;

if(count($productIds) > 0) {
    // ใช้ฟังก์ชันจาก config.php
    $conn = getConnection();
    $placeholders = implode(',', array_map(function($i) { return '$' . ($i + 1); }, range(0, count($productIds) - 1)));
    $query = "SELECT id, name, price, image_url, description, current_stock FROM products WHERE id IN ($placeholders)";
    $result = pg_query_params($conn, $query, $productIds);
    
    if ($result) {
        while ($product = pg_fetch_assoc($result)) {
            $products[] = $product;
        }
    }
    pg_close($conn);
}

// คำนวณยอดรวม
foreach ($products as $product) {
    $quantity = $_SESSION['cart'][$product['id']];
    $total_amount += $product['price'] * $quantity;
    $total_items += $quantity;
}

// บันทึก Activity Log (ถ้ามีฟังก์ชัน)
if (function_exists('logActivity')) {
    logActivity($_SESSION['user_id'], 'view', 'User viewed shopping cart', 'cart', null);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - ZapShop</title>
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

        .cart-summary-card {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid var(--makro-red);
            transition: all 0.3s ease;
        }

        .cart-summary-card:hover {
            box-shadow: var(--makro-shadow-hover);
            transform: translateY(-2px);
        }

        .summary-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--makro-light-gray);
        }

        .summary-header i {
            color: var(--makro-red);
            font-size: 1.5rem;
        }

        .summary-header h4 {
            margin: 0;
            color: var(--makro-red);
            font-weight: 600;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            text-align: center;
            padding: 15px;
            background: var(--makro-light-gray);
            border-radius: var(--makro-radius-sm);
            border: 1px solid var(--makro-border);
        }

        .summary-label {
            font-size: 0.9rem;
            color: var(--makro-gray);
            margin-bottom: 8px;
            display: block;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--makro-red);
        }

        .total-amount {
            font-size: 2rem;
            color: var(--makro-red);
        }

        .cart-container {
            background: white;
            border-radius: var(--makro-radius);
            box-shadow: var(--makro-shadow);
            overflow: hidden;
            margin-bottom: 25px;
            border: 1px solid var(--makro-border);
        }

        .cart-header {
            background: var(--makro-light-gray);
            padding: 20px 25px;
            display: grid;
            grid-template-columns: 80px 1fr 120px 120px 120px 100px;
            gap: 20px;
            font-weight: 600;
            color: var(--makro-gray);
            border-bottom: 2px solid var(--makro-border);
            align-items: center;
        }

        .cart-item {
            padding: 25px;
            border-bottom: 1px solid var(--makro-border);
            display: grid;
            grid-template-columns: 80px 1fr 120px 120px 120px 100px;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            background: var(--makro-light-gray);
            transform: translateY(-1px);
            box-shadow: var(--makro-shadow);
        }

        .cart-item.removing {
            opacity: 0;
            transform: translateX(-100%);
            transition: all 0.3s ease;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image-container {
            position: relative;
            width: 70px;
            height: 70px;
            border-radius: var(--makro-radius-sm);
            overflow: hidden;
            box-shadow: var(--makro-shadow);
        }

        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .cart-item:hover .product-image {
            transform: scale(1.05);
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
            line-height: 1.3;
        }

        .product-description {
            color: var(--makro-gray);
            font-size: 0.9rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-weight: 700;
            color: var(--makro-red);
            font-size: 1.2rem;
            text-align: center;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--makro-light-gray);
            padding: 8px;
            border-radius: var(--makro-radius-sm);
            border: 1px solid var(--makro-border);
        }

        .quantity-btn {
            background: var(--makro-red);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .quantity-btn:hover {
            background: var(--makro-red-dark);
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(227, 24, 55, 0.3);
        }

        .quantity-input {
            width: 50px;
            text-align: center;
            border: 1px solid var(--makro-border);
            border-radius: var(--makro-radius-sm);
            padding: 6px;
            font-weight: 600;
            color: #2c3e50;
        }

        .quantity-input:focus {
            outline: none;
            border-color: var(--makro-red);
            box-shadow: 0 0 0 3px rgba(227, 24, 55, 0.1);
        }

        .item-total {
            font-weight: 700;
            color: var(--makro-red);
            font-size: 1.2rem;
            text-align: center;
        }

        .remove-btn {
            background: var(--makro-red);
            color: white;
            border: none;
            padding: 10px;
            border-radius: var(--makro-radius-sm);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }

        .remove-btn:hover {
            background: var(--makro-red-dark);
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(227, 24, 55, 0.3);
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: var(--makro-gray);
        }

        .empty-cart i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--makro-red);
            opacity: 0.7;
        }

        .empty-cart h3 {
            color: var(--makro-gray);
            margin-bottom: 15px;
        }

        .empty-cart p {
            margin-bottom: 25px;
            font-size: 1.1rem;
        }

        .action-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-continue {
            background: linear-gradient(135deg, var(--makro-blue) 0%, #0056b3 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: var(--makro-radius-sm);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--makro-shadow);
            position: relative;
            overflow: hidden;
        }

        .btn-continue::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-continue:hover {
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--makro-shadow-hover);
            text-decoration: none;
            background: linear-gradient(135deg, #0056b3 0%, var(--makro-blue) 100%);
        }

        .btn-continue:hover::before {
            left: 100%;
        }

        .btn-continue i {
            transition: transform 0.3s ease;
        }

        .btn-continue:hover i {
            transform: translateX(-3px);
        }

        .btn-checkout {
            display: block;                  /* ให้เต็มความกว้าง */
            width: 100%;
            background: linear-gradient(to right, #b30000, #e60000); /* ไล่เฉดสีแดง */
            color: #fff;                     /* ตัวอักษรสีขาว */
            text-align: center;              /* จัดให้อยู่กลาง */
            padding: 15px 0;                 /* ขนาดสูงของปุ่ม */
            font-size: 18px;                 /* ขนาดตัวอักษร */
            font-weight: bold;               /* ทำให้ตัวหนา */
            border-radius: 8px;              /* ขอบโค้งมน */
            text-decoration: none;           /* ลบขีดเส้นใต้ */
            transition: 0.3s;                /* ทำให้ hover ลื่นไหล */
            position: relative;
            overflow: hidden;
        }

        .btn-checkout i {
            margin-right: 8px;               /* เว้นระยะระหว่างไอคอนกับข้อความ */
            font-size: 1.2rem;
        }

        .btn-checkout:hover {
            background: linear-gradient(to right, #cc0000, #ff1a1a); /* โทนแดงอ่อนขึ้น */
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(179, 0, 0, 0.3);
        }

        .btn-checkout:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(179, 0, 0, 0.3);
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

        .stagger-animation {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .stagger-animation:nth-child(1) { animation-delay: 0.1s; }
        .stagger-animation:nth-child(2) { animation-delay: 0.2s; }
        .stagger-animation:nth-child(3) { animation-delay: 0.3s; }
        .stagger-animation:nth-child(4) { animation-delay: 0.4s; }
        .stagger-animation:nth-child(5) { animation-delay: 0.5s; }

        @media (max-width: 992px) {
            .cart-header, .cart-item {
                grid-template-columns: 70px 1fr 100px 100px 100px 80px;
                gap: 15px;
                padding: 20px;
            }
            
            .summary-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }
            
            .cart-header {
                display: none;
            }
            
            .cart-item {
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 20px;
                text-align: center;
            }
            
            .product-image-container {
                margin: 0 auto;
            }
            
            .product-info {
                text-align: center;
            }
            
            .product-description {
                display: none;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-continue, .btn-checkout {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                padding: 30px 0;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .cart-summary-card {
                padding: 20px;
            }
            
            .cart-item {
                padding: 15px;
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
                <i class="fas fa-shopping-cart"></i> ตะกร้าสินค้า
            </h1>
            <p class="page-subtitle">ตรวจสอบและจัดการสินค้าในตะกร้าของคุณ</p>
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

    <div class="cart-summary-card fade-in-up">
        <div class="summary-header">
            <i class="fas fa-chart-bar"></i>
            <h4>สรุปตะกร้าสินค้า</h4>
        </div>
        <div class="summary-grid">
            <div class="summary-item">
                <span class="summary-label">จำนวนสินค้า</span>
                <span class="summary-value"><?php echo $total_items; ?> ชิ้น</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">จำนวนรายการ</span>
                <span class="summary-value"><?php echo count($products); ?> รายการ</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">ยอดรวม</span>
                <span class="summary-value total-amount">฿<?php echo number_format($total_amount, 2); ?></span>
            </div>
        </div>
    </div>

    <?php if (count($products) > 0): ?>
        <div class="cart-container fade-in-up">
            <div class="cart-header">
                <div>รูปภาพ</div>
                <div>สินค้า</div>
                <div>ราคา</div>
                <div>จำนวน</div>
                <div>ยอดรวม</div>
                <div>จัดการ</div>
            </div>
            
            <?php foreach ($products as $index => $product): ?>
                <div class="cart-item stagger-animation">
                    <div>
                        <?php 
                        $image_path = '';
                        if (!empty($product['image_url'])) {
                            // ตรวจสอบว่าเป็น data URI (base64) หรือไม่
                            if (strpos($product['image_url'], 'data:') === 0) {
                                $image_path = $product['image_url'];
                            }
                            // ตรวจสอบว่าเป็น URL ภายนอกหรือไม่
                            elseif (filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                                $image_path = $product['image_url'];
                            } 
                            // ตรวจสอบว่าเป็น placeholder หรือไม่
                            elseif (strpos($product['image_url'], '[1]') !== false) {
                                $image_path = 'https://placehold.co/70x70/cccccc/333333?text=Image';
                            } 
                            // ถ้าเป็นชื่อไฟล์ปกติ
                            else {
                                $image_path = 'upload_image/' . htmlspecialchars($product['image_url']);
                            }
                        } else {
                            $image_path = 'https://placehold.co/70x70/cccccc/333333?text=No+Image';
                        }
                        ?>
                        <div class="product-image-container">
                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-description"><?php echo htmlspecialchars($product['description'] ?: 'ไม่มีรายละเอียด'); ?></div>
                    </div>
                    <div class="product-price">฿<?php echo number_format($product['price'], 2); ?></div>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="updateQuantity('<?php echo htmlspecialchars($product['id'], ENT_QUOTES); ?>', -1)" title="ลดจำนวน">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" class="quantity-input" value="<?php echo $_SESSION['cart'][$product['id']]; ?>" 
                               min="1" max="<?php echo $product['current_stock']; ?>" 
                               data-product-id="<?php echo htmlspecialchars($product['id'], ENT_QUOTES); ?>"
                               onchange="handleQuantityChange('<?php echo htmlspecialchars($product['id'], ENT_QUOTES); ?>', this)"
                               oninput="this.value = Math.max(1, Math.min(<?php echo $product['current_stock']; ?>, parseInt(this.value) || 1))">
                        <button class="quantity-btn" onclick="updateQuantity('<?php echo htmlspecialchars($product['id'], ENT_QUOTES); ?>', 1)" title="เพิ่มจำนวน">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div class="item-total">฿<?php echo number_format($product['price'] * $_SESSION['cart'][$product['id']], 2); ?></div>
                    <div>
                        <button class="remove-btn" onclick="removeItem('<?php echo htmlspecialchars($product['id'], ENT_QUOTES); ?>')" title="ลบสินค้า">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="action-buttons fade-in-up">
            <a href="product-list1.php" class="btn-continue">
                <i class="fas fa-arrow-left"></i> เลือกสินค้าต่อ
            </a>
            <a href="payment-methods.php" class="btn-checkout">
                <i class="fas fa-credit-card"></i> ดำเนินการสั่งซื้อ
            </a>
        </div>
    <?php else: ?>
        <div class="cart-container fade-in-up">
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>ตะกร้าสินค้าว่าง</h3>
                <p>คุณยังไม่มีสินค้าในตะกร้า</p>
                <a href="product-list1.php" class="btn-checkout">
                    <i class="fas fa-shopping-bag"></i> เลือกสินค้า
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
// เก็บข้อมูลสินค้าไว้ในตัวแปร JavaScript
try {
    const cartData = <?php echo json_encode($_SESSION['cart'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    const productPrices = <?php echo json_encode(array_column($products, 'price', 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
} catch (e) {
    console.error('Error initializing cart data:', e);
    const cartData = {};
    const productPrices = {};
}

// ฟังก์ชันอัปเดตจำนวนสินค้า (Frontend-first)
function updateQuantity(productId, change) {
    console.log('updateQuantity called:', productId, change);
    
    // หา input field ที่มี productId นี้
    const quantityInput = document.querySelector(`input[data-product-id="${productId}"]`);
    if (!quantityInput) {
        console.error('Quantity input not found for product:', productId);
        return;
    }
    
    const currentQty = parseInt(quantityInput.value, 10);
    const newQty = Math.max(1, currentQty + change);
    
    console.log('Updating quantity from', currentQty, 'to', newQty);
    
    // อัปเดตค่าใน input ทันที
    quantityInput.value = newQty;
    
    // อัปเดตยอดรวมของรายการนี้
    updateItemTotal(productId, newQty);
    
    // อัปเดตยอดรวมทั้งหมด
    updateCartTotal();
    
    // อัปเดตข้อมูลใน session (optional - สำหรับ sync กับ backend)
    updateSessionCart(productId, newQty);
    
    // แสดง notification
    showNotification(`อัปเดตจำนวนสินค้าเป็น ${newQty} ชิ้นแล้ว`, 'success');
}

// ฟังก์ชันอัปเดตยอดรวมของรายการ
function updateItemTotal(productId, quantity) {
    const price = productPrices[productId] || 0;
    const total = price * quantity;
    
    // หา element ที่แสดงยอดรวมของรายการนี้
    const quantityInput = document.querySelector(`input[data-product-id="${productId}"]`);
    if (!quantityInput) return;
    
    const cartItem = quantityInput.closest('.cart-item');
    const itemTotalElement = cartItem.querySelector('.item-total');
    
    if (itemTotalElement) {
        itemTotalElement.textContent = `฿${total.toLocaleString('th-TH', {minimumFractionDigits: 2})}`;
    }
}

// ฟังก์ชันอัปเดตยอดรวมทั้งหมด
function updateCartTotal() {
    let totalAmount = 0;
    let totalItems = 0;
    
    // คำนวณยอดรวมจากทุกรายการ
    document.querySelectorAll('.cart-item').forEach(item => {
        const quantityInput = item.querySelector('.quantity-input');
        const itemTotalElement = item.querySelector('.item-total');
        
        if (quantityInput && itemTotalElement) {
            const quantity = parseInt(quantityInput.value, 10);
            const itemTotal = parseFloat(itemTotalElement.textContent.replace('฿', '').replace(/,/g, ''));
            
            totalAmount += itemTotal;
            totalItems += quantity;
        }
    });
    
    // อัปเดตการแสดงผล
    const totalAmountElement = document.querySelector('.total-amount');
    const totalItemsElements = document.querySelectorAll('.summary-value');
    
    if (totalAmountElement) {
        totalAmountElement.textContent = `฿${totalAmount.toLocaleString('th-TH', {minimumFractionDigits: 2})}`;
    }
    
    // อัปเดตจำนวนสินค้าทั้งหมด (element แรก)
    if (totalItemsElements.length > 0) {
        totalItemsElements[0].textContent = `${totalItems} ชิ้น`;
    }
}

// ฟังก์ชันอัปเดต session cart (สำหรับ sync กับ backend)
function updateSessionCart(productId, quantity) {
    // ส่งข้อมูลไป backend แบบ async (ไม่ต้องรอ response)
    fetch('cart-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.warn('Backend sync failed:', data.message);
            // ถ้า backend sync ล้มเหลว อาจจะแสดง notification หรือ log
        }
    })
    .catch(error => {
        console.warn('Backend sync error:', error);
        // ไม่ต้องแสดง error ให้ user เพราะ frontend ทำงานได้แล้ว
    });
}

// ฟังก์ชันลบสินค้า
function removeItem(productId) {
    console.log('removeItem called:', productId);
    
    if (confirm('คุณต้องการลบสินค้านี้ออกจากตะกร้าหรือไม่?')) {
        const quantityInput = document.querySelector(`input[data-product-id="${productId}"]`);
        if (!quantityInput) {
            console.error('Quantity input not found for product:', productId);
            return;
        }
        
        const cartItem = quantityInput.closest('.cart-item');
        
        // ลบออกจากหน้าเว็บทันที
        cartItem.classList.add('removing');
        
        setTimeout(() => {
            cartItem.remove();
            updateCartTotal();
            
            // แสดง notification
            showNotification('ลบสินค้าออกจากตะกร้าแล้ว', 'info');
            
            // ตรวจสอบว่าตะกร้าว่างหรือไม่
            const remainingItems = document.querySelectorAll('.cart-item');
            if (remainingItems.length === 0) {
                showEmptyCart();
            }
        }, 300);
        
        // ส่งข้อมูลไป backend
        fetch('cart-delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.warn('Backend delete failed:', data.message);
            }
        })
        .catch(error => {
            console.warn('Backend delete error:', error);
        });
    }
}

// ฟังก์ชันแสดงตะกร้าว่าง
function showEmptyCart() {
    const cartContainer = document.querySelector('.cart-container');
    cartContainer.innerHTML = `
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>ตะกร้าสินค้าว่าง</h3>
            <p>คุณยังไม่มีสินค้าในตะกร้า</p>
            <a href="product-list1.php" class="btn-checkout">
                <i class="fas fa-shopping-bag"></i> เลือกสินค้า
            </a>
        </div>
    `;
}

// ฟังก์ชันแสดง notification
function showNotification(message, type = 'success') {
    // สร้าง notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // เพิ่มเข้าไปในหน้าเว็บ
    document.body.appendChild(notification);
    
    // ลบออกหลังจาก 3 วินาที
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// ฟังก์ชันจัดการการเปลี่ยนแปลง input โดยตรง
function handleQuantityChange(productId, inputElement) {
    console.log('handleQuantityChange called:', productId, inputElement.value);
    
    const newQty = parseInt(inputElement.value, 10);
    const maxStock = parseInt(inputElement.getAttribute('max'), 10);
    
    // ตรวจสอบค่าที่ป้อน
    if (isNaN(newQty) || newQty < 1) {
        inputElement.value = 1;
        showNotification('จำนวนสินค้าต้องมากกว่า 0', 'warning');
        updateItemTotal(productId, 1);
        updateCartTotal();
        updateSessionCart(productId, 1);
        return;
    }
    
    if (newQty > maxStock) {
        inputElement.value = maxStock;
        showNotification(`จำนวนสินค้าเกินสต็อกที่มี (สูงสุด ${maxStock} ชิ้น)`, 'warning');
        updateItemTotal(productId, maxStock);
        updateCartTotal();
        updateSessionCart(productId, maxStock);
        return;
    }
    
    // อัปเดตยอดรวม
    updateItemTotal(productId, newQty);
    updateCartTotal();
    updateSessionCart(productId, newQty);
    
    // แสดง notification
    showNotification(`อัปเดตจำนวนสินค้าเป็น ${newQty} ชิ้นแล้ว`, 'success');
}

// Add hover effects for cart items
document.addEventListener('DOMContentLoaded', function() {
    const cartItems = document.querySelectorAll('.cart-item');
    
    cartItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // อัปเดตยอดรวมครั้งแรก
    updateCartTotal();
});
</script>
</body>
</html>