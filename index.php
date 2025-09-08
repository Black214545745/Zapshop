<?php
session_start();
include 'config.php';

// ใช้ฟังก์ชันจาก config.php เพื่อดึงข้อมูลสินค้า
$products = getAllProducts();

// บันทึก Activity Log สำหรับการเข้าชมหน้าแรก
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'view', 'User visited homepage', 'pages', null);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZapShop - ร้านค้าออนไลน์คุณภาพ</title>
    
    <!-- Bootstrap 5.3.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6.4.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Design System -->
    <link href="assets/css/zapshop-design-system.css" rel="stylesheet">
    
    <!-- ZapShop Button System -->
    <link href="assets/css/zapshop-buttons.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        /* ===== NAVIGATION ===== */
        .custom-navbar {
            background-color: #e53e3e !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: white !important;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        .brand-logo i {
            color: #e53e3e;
            font-size: 1.2rem;
        }

        .brand-text {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            margin: 0 auto;
        }

        .nav-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.8rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            min-width: 80px;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .nav-btn.active {
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .nav-btn i {
            font-size: 1.2rem;
            margin-bottom: 0.3rem;
        }

        .nav-btn span {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .user-info i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .user-info span {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .menu-btn {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .menu-btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .menu-btn span {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .login-btn {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            text-decoration: none;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .login-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .login-btn i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }

        .login-btn span {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* ===== HERO SECTION ===== */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark), #d4143a);
            color: var(--white);
            padding: var(--spacing-3xl) 0;
            position: relative;
            overflow: hidden;
            min-height: 90vh;
            display: flex;
            align-items: center;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .hero-content-grid {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 2rem;
            align-items: center;
            width: 100%;
        }

        .hero-center {
            text-align: center;
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            margin-bottom: var(--spacing-lg);
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: slideInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.4rem);
            margin-bottom: var(--spacing-xl);
            opacity: 0.95;
            animation: slideInUp 1s ease-out 0.2s both;
        }

        .hero-cta {
            animation: slideInUp 1s ease-out 0.4s both;
        }

        /* Hero Promotion Cards */
        .hero-promo-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-lg);
            padding: 3rem;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            height: 500px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-promo-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }

        .hero-promo-content {
            position: relative;
            z-index: 2;
        }

        .hero-promo-text {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-promo-title {
            font-size: 4rem;
            font-weight: 900;
            margin: 0;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitter 2s ease-in-out infinite alternate;
        }

        .hero-promo-subtitle {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitter 2s ease-in-out infinite alternate 0.5s;
        }

        .hero-promo-sale {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitter 2s ease-in-out infinite alternate 1s;
        }

        .hero-promo-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.3;
            z-index: 1;
        }

        .hero-promo-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(1.1);
            transition: transform 0.3s ease;
        }

        .hero-promo-card:hover .hero-promo-image img {
            transform: scale(1.2);
        }

        .hero-promo-badge {
            background: rgba(220, 53, 69, 0.9);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }

        .hero-promo-badge h3 {
            margin: 0 0 1rem 0;
            font-size: 2rem;
            font-weight: bold;
        }

        .hero-discount {
            font-size: 3.5rem;
            font-weight: 900;
            margin: 0;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== FEATURES SECTION ===== */
        .features-section {
            padding: var(--spacing-3xl) 0;
            background: var(--white);
        }

        .feature-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-xl);
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-lg);
            color: var(--white);
            font-size: 2rem;
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: var(--spacing-md);
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* ===== PRODUCTS SECTION ===== */
        .products-section {
            padding: var(--spacing-3xl) 0;
            background: var(--background-light);
        }

        .section-title {
            text-align: center;
            margin-bottom: var(--spacing-3xl);
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
        }

        .section-title p {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .product-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
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
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-image[src*="placeholder"] {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .product-info {
            padding: var(--spacing-lg);
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: var(--spacing-sm);
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
        }

        .product-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: var(--spacing-md);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }


        /* ===== FOOTER ===== */
        .footer {
            background: var(--text-primary);
            color: var(--white);
            padding: var(--spacing-3xl) 0 var(--spacing-lg);
        }

        .footer h5 {
            font-weight: 600;
            margin-bottom: var(--spacing-md);
        }

        .footer a {
            color: var(--white);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary-color);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-lg);
            text-align: center;
        }

        /* ===== PROMOTION BANNERS ===== */
        .promotion-banners {
            padding: var(--spacing-3xl) 0;
            background: var(--background-light);
        }

        .promotion-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            height: 300px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .promotion-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }

        .left-promo {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
        }

        .right-promo {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .promotion-content {
            position: absolute;
            z-index: 2;
            text-align: center;
        }

        .promotion-text {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .promotion-title {
            font-size: 4rem;
            font-weight: 900;
            margin: 0;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitter 2s ease-in-out infinite alternate;
        }

        .promotion-subtitle {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitter 2s ease-in-out infinite alternate 0.5s;
        }

        .promotion-sale {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
            background: linear-gradient(45deg, #FFD700, #FFA500, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: glitter 2s ease-in-out infinite alternate 1s;
        }

        @keyframes glitter {
            0% { filter: brightness(1) drop-shadow(0 0 5px #FFD700); }
            100% { filter: brightness(1.2) drop-shadow(0 0 10px #FFD700); }
        }

        .promotion-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.3;
            z-index: 1;
        }

        .promotion-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .promotion-badge {
            background: rgba(220, 53, 69, 0.9);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .promotion-badge h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .promotion-badge .discount {
            font-size: 3rem;
            font-weight: 900;
            margin: 0;
        }

        /* ===== BOTTOM PROMOTION ===== */
        .bottom-promotion {
            padding: 2rem 0;
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: white;
            overflow: hidden;
        }

        .scrolling-promotion {
            width: 100%;
            overflow: hidden;
            position: relative;
        }

        .scrolling-text {
            display: flex;
            white-space: nowrap;
            animation: scroll 30s linear infinite;
            font-size: 1.2rem;
            font-weight: 500;
            color: white;
        }

        .scrolling-text span {
            margin-right: 4rem;
            display: inline-block;
            padding: 0.5rem 0;
        }

        @keyframes scroll {
            0% { 
                transform: translateX(100%); 
            }
            100% { 
                transform: translateX(-100%); 
            }
        }

        /* Pause animation on hover */
        .scrolling-text:hover {
            animation-play-state: paused;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .hero-section {
                min-height: 60vh;
                padding: var(--spacing-xl) 0;
            }
            
            .hero-content-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .hero-promo-title {
                font-size: 2.5rem;
            }

            .hero-promo-subtitle {
                font-size: 1.8rem;
            }

            .hero-promo-sale {
                font-size: 1.5rem;
            }

            .hero-discount {
                font-size: 2rem;
            }
            
            .feature-card,
            .product-card {
                margin-bottom: var(--spacing-lg);
            }

            .promotion-announcement {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }

            .announcement-title {
                font-size: 2rem;
            }

            .scrolling-text {
                font-size: 1rem;
            }
            
            .scrolling-text span {
                margin-right: 2rem;
            }

            /* Navigation Responsive */
            .nav-buttons {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }

            .nav-btn {
                flex-direction: row;
                justify-content: flex-start;
                padding: 0.6rem 1rem;
                min-width: auto;
            }

            .nav-btn i {
                margin-bottom: 0;
                margin-right: 0.5rem;
            }

            .user-section {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
                margin-top: 1rem;
            }

            .user-info,
            .menu-btn,
            .login-btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* ===== ANIMATIONS ===== */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== LOADING STATES ===== */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top custom-navbar">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand" href="index.php">
                <div class="brand-logo">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <span class="brand-text">ZapShop</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Navigation Buttons -->
                <div class="nav-buttons">
                    <a href="index.php" class="nav-btn active">
                        <i class="fas fa-home"></i>
                        <span>หน้าแรก</span>
                    </a>
                    <a href="product-list1.php" class="nav-btn">
                        <i class="fas fa-box"></i>
                        <span>สินค้าทั้งหมด</span>
                    </a>
                    <a href="cart.php" class="nav-btn">
                        <i class="fas fa-shopping-cart"></i>
                        <span>ตะกร้า</span>
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- User Section -->
                <div class="user-section">
                    <?php if (isset($_SESSION['username'])): ?>
                        <div class="user-info">
                            <i class="fas fa-user"></i>
                            <span>สวัสดี, สหภาพ อ่อนจันทร์</span>
                        </div>
                        <div class="menu-btn">
                            <i class="fas fa-bars"></i>
                            <span>เมนู</span>
                        </div>
                    <?php else: ?>
                        <a href="user-login.php" class="login-btn">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>เข้าสู่ระบบ</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content-grid">
                <!-- Left Promotion -->
                <div class="hero-promo-left">
                    <div class="hero-promo-card">
                        <div class="hero-promo-content">
                            <div class="hero-promo-text">
                                <h2 class="hero-promo-title">10.10</h2>
                                <h3 class="hero-promo-subtitle">MAGA</h3>
                                <h4 class="hero-promo-sale">SALE</h4>
                            </div>
                        </div>
                        <div class="hero-promo-image">
                            <img src="img/pro/Screenshot 2025-09-05 214444.png" alt="10.10 MAGA SALE" class="img-fluid">
                        </div>
                    </div>
                </div>
                
                <!-- Center Welcome -->
                <div class="hero-center">
                    <h1 class="hero-title">ยินดีต้อนรับสู่ ZapShop</h1>
                    <p class="hero-subtitle">ร้านค้าออนไลน์คุณภาพ จำหน่ายสินค้าหลากหลาย ราคาเป็นมิตร</p>
                    <div class="hero-cta">
                                            <a href="product-list1.php" class="btn btn-light btn-lg me-3">
                        <i class="fas fa-shopping-bag me-2"></i>ช้อปปิ้งเลย
                    </a>
                        <a href="#features" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>เรียนรู้เพิ่มเติม
                        </a>
                    </div>
                </div>
                
                <!-- Right Promotion -->
                <div class="hero-promo-right">
                    <div class="hero-promo-card">
                        <div class="hero-promo-image">
                            <img src="img/pro/messageImage_1757083935322.jpg" alt="Fresh Meat 50%" class="img-fluid">
                        </div>
                        <div class="hero-promo-content">
                            <div class="hero-promo-badge">
                                <h3>Fresh Meat</h3>
                                <div class="hero-discount">50%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom Promotion Section -->
    <section class="bottom-promotion">
        <div class="container">
            <div class="scrolling-promotion">
                <div class="scrolling-text">
                    <span>🔥 โปรโมชัน 10.10 MAGA SALE - การลดราคาครั้งยิ่งใหญ่! 🔥</span>
                    <span>🥩 ลดราคาอาหารสด 50% - เนื้อหมู เนื้อไก่ เนื้อวัว สดใหม่ทุกวัน! 🥩</span>
                    <span>🛒 สั่งซื้อออนไลน์ รับของที่บ้าน ปลอดภัย สะดวก รวดเร็ว! 🛒</span>
                    <span>💎 คุณภาพดี ราคาถูก รับประกันความสดใหม่! 💎</span>
                    <span>🎉 จำกัดเวลาเท่านั้น! ไม่ควรพลาดโปรโมชันสุดพิเศษนี้! 🎉</span>
                </div>
            </div>
        </div>
    </section>


    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <div class="section-title">
                <h2>สินค้าแนะนำ</h2>
                <p>สินค้าคุณภาพดี ราคาเป็นมิตร ที่เราเลือกมาให้คุณ</p>
            </div>
            
            <div class="row">
                <?php if (!empty($products)): ?>
                    <?php foreach (array_slice($products, 0, 4) as $product): ?>
                        <div class="col-lg-6 col-md-6 mb-4">
                            <div class="product-card fade-in">
                                <div class="product-image-container">
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="product-image" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                            <span style="font-size: 3rem; color: #ccc;">📦</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h4 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <div class="product-price">฿<?php echo number_format($product['price'], 2); ?></div>
                                    <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <div class="d-flex gap-2">
                                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-outline-primary btn-sm flex-fill">
                                            <i class="fas fa-eye me-1"></i>ดูรายละเอียด
                                        </a>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="addToCart('<?php echo $product['id']; ?>')">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">ไม่มีสินค้าในขณะนี้</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="product-list1.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-box me-2"></i>ดูสินค้าทั้งหมด
                </a>
            </div>
        </div>
    </section>


    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h3 class="feature-title">จัดส่งเร็ว</h3>
                        <p class="feature-description">จัดส่งสินค้าอย่างรวดเร็ว ปลอดภัย ถึงมือคุณในเวลาที่กำหนด</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">ปลอดภัย</h3>
                        <p class="feature-description">ระบบชำระเงินที่ปลอดภัย ข้อมูลส่วนตัวได้รับการปกป้อง</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h3 class="feature-title">บริการลูกค้า</h3>
                        <p class="feature-description">ทีมงานพร้อมให้บริการและตอบคำถามทุกข้อสงสัย</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5><i class="fas fa-shopping-cart me-2"></i>ZapShop</h5>
                    <p>ร้านค้าออนไลน์คุณภาพ จำหน่ายสินค้าหลากหลาย ราคาเป็นมิตร</p>
                    <div class="d-flex gap-3">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-line"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>ลิงก์ด่วน</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">หน้าแรก</a></li>
                        <li><a href="product-list1.php">สินค้าทั้งหมด</a></li>
                        <li><a href="cart.php">ตะกร้า</a></li>
                        <li><a href="about.php">เกี่ยวกับเรา</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>บริการลูกค้า</h5>
                    <ul class="list-unstyled">
                        <li><a href="contact.php">ติดต่อเรา</a></li>
                        <li><a href="faq.php">คำถามที่พบบ่อย</a></li>
                        <li><a href="shipping.php">ข้อมูลการจัดส่ง</a></li>
                        <li><a href="return.php">การคืนสินค้า</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>ติดต่อเรา</h5>
                    <p><i class="fas fa-phone me-2"></i>02-123-4567</p>
                    <p><i class="fas fa-envelope me-2"></i>info@zapshop.com</p>
                    <p><i class="fas fa-map-marker-alt me-2"></i>กรุงเทพมหานคร</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 ZapShop. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Add to Cart Function
        function addToCart(productId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว!', 'success');
                    
                    // Update cart badge
                    updateCartBadge();
                } else {
                    showNotification('เกิดข้อผิดพลาด: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('เกิดข้อผิดพลาดในการเพิ่มสินค้า', 'error');
            });
        }

        // Update Cart Badge
        function updateCartBadge() {
            fetch('get_cart_count.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.badge');
                if (badge) {
                    badge.textContent = data.count;
                }
            });
        }

        // Show Notification
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe all fade-in elements
        document.querySelectorAll('.fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn)) {
    pg_close($conn);
}
?>