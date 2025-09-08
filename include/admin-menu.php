<?php
// C:/xampp/htdocs/shoppingcart/include/admin-menu.php

// ตรวจสอบว่ามีการเริ่มต้น session แล้วหรือยัง (ป้องกันการเรียกซ้ำ)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// กำหนด base_url ถ้ายังไม่ได้กำหนด
if (!isset($base_url)) {
    $base_url = '';
} 
?>

<header class="sticky-top admin-navbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
            <!-- Logo และชื่อ -->
            <div class="d-flex align-items-center">
                <i class="fas fa-tachometer-alt text-white me-2 fs-4"></i>
                <span class="fw-bold fs-3 text-white">ZapShop Admin</span>
            </div>
            
            <!-- เมนูหลัก -->
            <nav class="d-flex align-items-center">
                <ul class="nav mb-0">
                    <li class="nav-item me-3">
                        <a href="<?php echo $base_url; ?>admin-dashboard.php" class="nav-link text-white fw-bold admin-nav-link<?php if(basename($_SERVER['PHP_SELF'])=='admin-dashboard.php') echo ' active'; ?>">
                            <i class="fas fa-tachometer-alt me-2"></i>แดชบอร์ด
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <a href="<?php echo $base_url; ?>admin-products.php" class="nav-link text-white fw-bold admin-nav-link<?php if(basename($_SERVER['PHP_SELF'])=='admin-products.php') echo ' active'; ?>">
                            <i class="fas fa-shopping-bag me-2"></i>จัดการสินค้า
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <a href="<?php echo $base_url; ?>admin-users.php" class="nav-link text-white fw-bold admin-nav-link<?php if(basename($_SERVER['PHP_SELF'])=='admin-users.php') echo ' active'; ?>">
                            <i class="fas fa-users me-2"></i>จัดการผู้ใช้
                        </a>
                    </li>
                    <li class="nav-item me-3">
                        <a href="<?php echo $base_url; ?>admin-activity-logs.php" class="nav-link text-white fw-bold admin-nav-link<?php if(basename($_SERVER['PHP_SELF'])=='admin-activity-logs.php') echo ' active'; ?>">
                            <i class="fas fa-history me-2"></i>กิจกรรม
                        </a>
                    </li>
                </ul>
                
                <!-- ข้อมูลผู้ใช้และปุ่มออกจากระบบ -->
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="fas fa-user me-2"></i>สวัสดี, <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>
                    </span>
                    <a href="<?php echo $base_url; ?>admin-login.php" class="btn btn-danger fw-bold admin-logout-btn">
                        <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                    </a>
                </div>
            </nav>
        </div>
    </div>
</header>
<style>
.admin-navbar {
    background: #e53e3e;
    padding: 1rem 0;
    border-bottom: 1px solid #c53030;
}
.admin-nav-link {
    color: white !important;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    transition: all 0.3s ease;
}
.admin-nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white !important;
}
.admin-nav-link.active {
    background: rgba(255,255,255,0.2);
    color: white !important;
}
.admin-logout-btn {
    background: #dc3545;
    border: none;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    text-decoration: none;
    transition: all 0.3s ease;
}
.admin-logout-btn:hover {
    background: #c82333;
    color: white;
}
@media (max-width: 991px) {
    .admin-navbar .d-flex {
        flex-direction: column;
        align-items: stretch;
    }
    .admin-navbar nav {
        width: 100%;
        margin-top: 1rem;
    }
    .admin-navbar .nav {
        justify-content: center;
    }
}
@media (max-width: 767px) {
    .admin-nav-link {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }
    .admin-logout-btn {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }
}
</style>