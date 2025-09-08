<?php
// PHP session start และการตรวจสอบการเข้าสู่ระบบ หากต้องการบังคับล็อกอิน
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header("Location: user-login.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เกี่ยวกับเรา | ชื่อร้านค้าของคุณ</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav>
            <div class="logo">ชื่อร้านค้า</div>
            <ul>
                <li><a href="index.html">หน้าหลัก</a></li>
                <li><a href="#" id="openUserLoginPopup">เข้าสู่ระบบ</a></li>
                <li><a href="#" id="openUserRegisterPopup">ลงทะเบียน</a></li>
                <li><a href="about.php">เกี่ยวกับเรา</a></li>
                <li><a href="contact.php">ติดต่อ</a></li>
                <li><a href="#" id="openAdminLoginPopup">เข้าสู่ระบบ Admin</a></li>
            </ul>
        </nav>
    </header>

    <main class="page-container">
        <section class="content-card">
            <h1 class="page-title">เกี่ยวกับเรา</h1>
            <p><strong>ชื่อร้านค้าของคุณ</strong> มุ่งมั่นที่จะเป็นแหล่งรวมสินค้าคุณภาพดี สดใหม่ และราคาเป็นกันเอง สำหรับทุกครอบครัว เราเริ่มต้นจากการเป็นร้านค้าเล็กๆ ด้วยความตั้งใจที่จะส่งมอบสิ่งที่ดีที่สุดให้กับลูกค้า และเติบโตมาอย่างต่อเนื่องด้วยความไว้วางใจจากทุกท่าน</p>
            
            <p>เราคัดสรรสินค้าจากแหล่งผลิตที่เชื่อถือได้ ทั้งผักผลไม้สดจากเกษตรกรท้องถิ่น เนื้อสัตว์คุณภาพเยี่ยม ผลิตภัณฑ์นม ขนมปังอบใหม่ และสินค้าอุปโภคบริโภคหลากหลายประเภท เพื่อให้คุณมั่นใจได้ว่าทุกสิ่งที่คุณซื้อจากเรามีคุณภาพและปลอดภัย</p>
            
            <div class="image-gallery">
                <img src="https://placehold.co/400x250/dc3545/ffffff?text=Our+Store" alt="Our Store">
                <img src="https://placehold.co/400x250/dc3545/ffffff?text=Fresh+Products" alt="Fresh Products">
                <img src="https://placehold.co/400x250/dc3545/ffffff?text=Happy+Customers" alt="Happy Customers">
            </div>

            <h3>วิสัยทัศน์ของเรา</h3>
            <p>เป็นซูเปอร์มาร์เก็ตชั้นนำที่ได้รับความไว้วางใจจากลูกค้าในด้านคุณภาพสินค้า บริการที่เป็นเลิศ และความรับผิดชอบต่อสังคม</p>

            <h3>พันธกิจ</h3>
            <ul>
                <li>จัดหาสินค้าคุณภาพสูง สดใหม่ และหลากหลาย</li>
                <li>มอบประสบการณ์การช้อปปิ้งที่สะดวกสบายและน่าพึงพอใจ</li>
                <li>สนับสนุนเกษตรกรและผู้ผลิตท้องถิ่น</li>
                <li>ส่งเสริมสุขภาพและคุณภาพชีวิตที่ดีของชุมชน</li>
            </ul>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 ชื่อร้านค้าของคุณ. สงวนลิขสิทธิ์.</p>
    </footer>

    <!-- เนื่องจากหน้านี้อาจถูกเข้าถึงโดยตรง Pop-up HTML และ script.js จำเป็นต้องอยู่ด้วย -->
    <!-- POPUP CONTAINER สำหรับ User Login -->
    <div id="userLoginPopup" class="popup-overlay">
        <div class="popup-content">
            <span class="close-btn">&times;</span>
            <h2>เข้าสู่ระบบ (ลูกค้า)</h2>
            <form class="auth-form" action="user-login.php" method="POST">
                <div class="form-group">
                    <label for="user-login-username">ชื่อผู้ใช้:</label>
                    <input type="text" id="user-login-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="user-login-password">รหัสผ่าน:</label>
                    <input type="password" id="user-login-password" name="password" required>
                </div>
                <button type="submit" class="auth-submit-btn">เข้าสู่ระบบ</button>
                <a href="#" class="forgot-password">ลืมรหัสผ่าน?</a>
            </form>
            <p class="text-center mt-3">ยังไม่มีบัญชี? <a href="#" id="openUserRegisterFromLogin">สมัครสมาชิก</a></p>
        </div>
    </div>

    <!-- POPUP CONTAINER สำหรับ User Register -->
    <div id="userRegisterPopup" class="popup-overlay">
        <div class="popup-content">
            <span class="close-btn">&times;</span>
            <h2>สมัครสมาชิก (ลูกค้า)</h2>
            <form class="auth-form" action="user-register.php" method="POST">
                <div class="form-group">
                    <label for="user-register-username">ชื่อผู้ใช้:</label>
                    <input type="text" id="user-register-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="user-register-email">อีเมล:</label>
                    <input type="email" id="user-register-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="user-register-password">รหัสผ่าน:</label>
                    <input type="password" id="user-register-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="user-register-confirm-password">ยืนยันรหัสผ่าน:</label>
                    <input type="password" id="user-register-confirm-password" name="confirm_password" required>
                </div>
                <button type="submit" class="auth-submit-btn">สมัครสมาชิก</button>
                <p class="text-center mt-3">มีบัญชีอยู่แล้ว? <a href="#" id="openUserLoginFromRegister">เข้าสู่ระบบ</a></p>
            </form>
        </div>
    </div>

    <!-- POPUP CONTAINER สำหรับ Admin Login -->
    <div id="adminLoginPopup" class="popup-overlay">
        <div class="popup-content">
            <span class="close-btn">&times;</span>
            <h2>เข้าสู่ระบบ (แอดมิน)</h2>
            <form class="auth-form" action="admin-login.php" method="POST">
                <div class="form-group">
                    <label for="admin-login-username">ชื่อผู้ใช้แอดมิน:</label>
                    <input type="text" id="admin-login-username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="admin-login-password">รหัสผ่านแอดมิน:</label>
                    <input type="password" id="admin-login-password" name="password" required>
                </div>
                <button type="submit" class="auth-submit-btn">เข้าสู่ระบบแอดมิน</button>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>
