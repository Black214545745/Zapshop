 <?php
// C:/xampp/htdocs/zap_shop/include/menu.php

// เริ่ม session หากยังไม่ได้เริ่ม
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['admin_username']);

// นับจำนวนสินค้าในตะกร้า
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $cart_item_count += $quantity;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            --secondary-gradient: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);
            --accent-gradient: linear-gradient(45deg, #ffc107 0%, #fd7e14 100%);
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.25);
            --red-glow: rgba(220, 53, 69, 0.3);
            --spacing-xs: 4px;
            --spacing-sm: 8px;
            --spacing-md: 12px;
            --spacing-lg: 16px;
            --spacing-xl: 24px;
        }

        body {
            font-family: 'Kanit', sans-serif;
        }

        /* Header สไตล์ใหม่ตามรูปภาพ */
        .zapstock-header {
            background: #dc3545;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Logo Area - ชิดซ้ายตลอด */
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
            justify-content: flex-start;
            margin-left: 0;
            flex-shrink: 0;
            min-width: 200px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            flex-shrink: 0;
        }

        .logo-icon i {
            color: #dc3545;
            font-size: 24px;
        }

        .brand-name {
            color: #ffffff;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            flex-shrink: 0;
            white-space: nowrap;
        }

        /* Navigation Menu - อยู่ตรงกลาง */
        .nav-menu {
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: center;
            flex: 1;
            margin: 0 20px;
        }

        .nav-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 12px 20px;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            min-width: 120px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        .nav-button:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            color: var(--white);
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .nav-button i {
            font-size: 18px;
            margin-bottom: 2px;
        }

        .nav-button span {
            font-size: 14px;
            font-weight: 500;
        }

        /* User Section - ชิดขวา */
        .user-section {
            display: flex;
            gap: 15px;
            align-items: center;
            justify-content: flex-end;
            margin-right: 0;
            flex-shrink: 0;
            min-width: 200px;
            position: relative;
        }

        .user-greeting {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 12px 20px;
            color: #ffffff;
            font-size: 14px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Hamburger Menu */
        .hamburger-menu {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 12px 20px;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            flex-shrink: 0;
            cursor: pointer;
            border: none;
            position: relative;
            z-index: 10000;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        .hamburger-menu:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            color: var(--white);
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .hamburger-menu:focus {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            outline: 2px solid rgba(255, 255, 255, 0.5);
            outline-offset: 2px;
        }

        .hamburger-menu:active {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Dropdown Menu */
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            min-width: 200px;
            z-index: 9999;
            border: 1px solid rgba(220, 53, 69, 0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 10px;
            will-change: transform, opacity, visibility;
        }

        .dropdown-menu.show {
            display: block;
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            animation: fadeInDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            color: #333333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(220, 53, 69, 0.1);
            outline: none;
        }

        .dropdown-menu a:focus {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            outline: 2px solid rgba(220, 53, 69, 0.3);
            outline-offset: -2px;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .dropdown-menu a:hover {
            background: rgba(220, 53, 69, 0.05);
            color: #dc3545;
            transform: translateX(5px);
            transition: all 0.3s ease;
        }

        .dropdown-menu i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
                visibility: hidden;
            }
            to {
                opacity: 1;
                transform: translateY(0);
                visibility: visible;
            }
        }

        .logout-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 25px;
            padding: 12px 20px;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            flex-shrink: 0;
        }

        .logout-button:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
            color: var(--white);
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        /* Cart Badge */
        .cart-badge {
            background: #ffd700;
            color: #333;
            font-size: 12px;
            font-weight: 700;
            min-width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            position: absolute;
            top: -8px;
            right: -8px;
            border: 2px solid var(--white);
        }

        /* Responsive Design - ปรับให้โลโก้ชิดซ้ายตลอด */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: row;
                gap: 10px;
                justify-content: space-between;
                padding: 0 15px;
            }

            .logo-section {
                justify-content: flex-start;
                margin-left: 0;
                flex-shrink: 0;
                min-width: 150px;
            }

            .nav-menu {
                flex-wrap: wrap;
                justify-content: center;
                flex: 1;
                margin: 0 10px;
            }

            .nav-button {
                min-width: 100px;
                padding: 10px 15px;
            }

            .user-section {
                justify-content: flex-end;
                margin-right: 0;
                flex-shrink: 0;
                min-width: 150px;
            }

            .brand-name {
                font-size: 24px;
            }

            /* ปรับ dropdown menu สำหรับ tablet */
            .dropdown-menu {
                right: 0;
                min-width: 200px;
                margin-top: 8px;
            }
        }

        @media (max-width: 480px) {
            .header-container {
                padding: 0 10px;
                gap: 5px;
            }

            .logo-section {
                justify-content: flex-start;
                margin-left: 0;
                gap: 10px;
                min-width: 120px;
            }

            .nav-button span {
                display: none;
            }

            .nav-button {
                min-width: 80px;
                padding: 10px;
            }

            .nav-menu {
                margin: 0 5px;
                gap: 10px;
            }

            .user-greeting span:not(.fas) {
                display: none;
            }

            .user-section {
                min-width: 120px;
            }

            .brand-name {
                font-size: 20px;
            }

            /* ปรับ dropdown menu สำหรับมือถือ */
            .dropdown-menu {
                right: 0;
                min-width: 180px;
                margin-top: 5px;
            }

            .hamburger-menu {
                padding: 10px 15px;
                font-size: 14px;
            }
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header class="zapstock-header fade-in">
        <div class="header-container">
            <!-- Logo Section -->
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-store"></i>
                </div>
                <h1 class="brand-name">ZapShop</h1>
            </div>

            <!-- Navigation Menu -->
            <div class="nav-menu">
                <a href="index.php" class="nav-button">
                    <i class="fas fa-home"></i>
                    <span>หน้าแรก</span>
                </a>
                <a href="product-list1.php" class="nav-button">
                    <i class="fas fa-boxes"></i>
                    <span>สินค้าทั้งหมด</span>
                </a>
                <?php if ($is_logged_in): ?>
                <a href="cart.php" class="nav-button position-relative">
                    <i class="fas fa-shopping-cart"></i>
                    <span>ตะกร้า</span>
                    <?php if ($cart_item_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_item_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- User Section -->
            <div class="user-section">
                <?php if ($is_logged_in): ?>
                    <div class="user-greeting">
                        <i class="fas fa-user"></i>
                        <span>สวัสดี, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    </div>
                    <button class="hamburger-menu" onclick="toggleMenu()">
                        <i class="fas fa-bars"></i>
                        <span>เมนู</span>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="profile.php">
                            <i class="fas fa-user-circle"></i>
                            โปรไฟล์
                        </a>
                        <a href="orders.php">
                            <i class="fas fa-list-alt"></i>
                            คำสั่งซื้อ
                        </a>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            ตั้งค่า
                        </a>
                        <a href="user-logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            ออกจากระบบ
                        </a>
                    </div>
                <?php elseif ($is_admin): ?>
                    <a href="admin-dashboard.php" class="logout-button">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>แดชบอร์ด</span>
                    </a>
                    <a href="admin-logout.php" class="logout-button">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                <?php elseif (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in']): ?>
                    <div class="user-dropdown">
                        <button class="user-button" onclick="toggleDropdown()">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo htmlspecialchars($_SESSION['customer_name'] ?? 'ลูกค้า'); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenu">
                            <a href="customer-profile.php">
                                <i class="fas fa-user-circle"></i>
                                โปรไฟล์
                            </a>
                            <a href="customer-orders.php">
                                <i class="fas fa-list-alt"></i>
                                คำสั่งซื้อ
                            </a>
                            <a href="customer-wishlist.php">
                                <i class="fas fa-heart"></i>
                                รายการโปรด
                            </a>
                            <a href="customer-addresses.php">
                                <i class="fas fa-map-marker-alt"></i>
                                ที่อยู่
                            </a>
                            <a href="customer-settings.php">
                                <i class="fas fa-cog"></i>
                                ตั้งค่า
                            </a>
                            <a href="customer-logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                ออกจากระบบ
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="customer-login.php" class="logout-button">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>เข้าสู่ระบบ</span>
                    </a>
                    <a href="customer-register.php" class="logout-button">
                        <i class="fas fa-user-plus"></i>
                        <span>ลงทะเบียน</span>
                    </a>
                    <a href="admin-login.php" class="logout-button">
                        <span>เข้าสู่ระบบแอดมิน</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันสำหรับอัปเดตจำนวนสินค้าในตะกร้า
        function updateCartCount(count) {
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = count;
                if (count > 0) {
                    cartBadge.style.display = 'flex';
                } else {
                    cartBadge.style.display = 'none';
                }
            }
        }

        // เพิ่มเอฟเฟกต์ hover ให้กับปุ่ม
        document.querySelectorAll('.nav-button, .logout-button').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // ฟังก์ชันสำหรับแสดง/ซ่อน Hamburger Menu
        function toggleMenu() {
            const dropdown = document.getElementById("dropdownMenu");
            if (dropdown) {
                const isVisible = dropdown.classList.contains("show");
                if (isVisible) {
                    dropdown.classList.remove("show");
                    console.log("Menu closed");
                } else {
                    dropdown.classList.add("show");
                    console.log("Menu opened");
                }
            } else {
                console.error("Dropdown menu not found!");
            }
        }

        // ปิดเมนูเมื่อคลิกที่อื่น
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById("dropdownMenu");
            const hamburger = document.querySelector('.hamburger-menu');
            
            if (dropdown && hamburger && !dropdown.contains(event.target) && !hamburger.contains(event.target)) {
                dropdown.classList.remove("show");
                console.log("Menu closed by clicking outside");
            }
        });

        // ปิดเมนูเมื่อคลิกที่ dropdown menu items
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById("dropdownMenu");
            if (dropdown && dropdown.contains(event.target) && event.target.tagName === 'A') {
                setTimeout(() => {
                    dropdown.classList.remove("show");
                    console.log("Menu closed by clicking menu item");
                }, 100);
            }
        });

        // ปิดเมนูเมื่อกด ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const dropdown = document.getElementById("dropdownMenu");
                if (dropdown) {
                    dropdown.classList.remove("show");
                    console.log("Menu closed by ESC key");
                }
            }
        });

        // เพิ่ม event listener สำหรับการโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Page loaded, hamburger menu ready");
            const dropdown = document.getElementById("dropdownMenu");
            const hamburger = document.querySelector('.hamburger-menu');
            
            if (dropdown && hamburger) {
                console.log("Hamburger menu elements found");
            } else {
                console.error("Hamburger menu elements not found");
            }
        });
    </script>
</body>
</html>