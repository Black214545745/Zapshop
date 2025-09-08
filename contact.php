<?php
// PHP session start และการตรวจสอบการเข้าสู่ระบบ หากต้องการบังคับล็อกอิน
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header("Location: user-login.php");
//     exit();
// }

// ส่วนนี้สามารถเพิ่ม Logic สำหรับการส่งฟอร์มติดต่อ (เช่น ส่งอีเมล)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $subject = htmlspecialchars($_POST['subject']);
    $message = htmlspecialchars($_POST['message']);

    // ตัวอย่างการแสดงผล (ในระบบจริงจะส่งอีเมลหรือบันทึกลงฐานข้อมูล)
    $_SESSION['contact_message'] = "ข้อความของคุณถูกส่งแล้ว: " . $subject;
    // อาจจะ redirect กลับไปหน้าเดิม หรือไปหน้า success
    // header("Location: contact.php?status=success");
    // exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดต่อเรา | ชื่อร้านค้าของคุณ</title>
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
            <h1 class="page-title">ติดต่อเรา</h1>
            <p>หากคุณมีคำถาม ข้อเสนอแนะ หรือต้องการความช่วยเหลือ โปรดอย่าลังเลที่จะติดต่อเรา เรายินดีให้บริการ!</p>

            <div class="contact-info">
                <p><strong>ที่อยู่:</strong> 171/403 ถนนประชาสโมสร 10000</p>
                <p><strong>โทรศัพท์:</strong> 012-345-6789</p>
                <p><strong>อีเมล:</strong> <a href="mailto:info@yourstore.com">jakkapant32@gmail.com</a></p>
                <p><strong>เวลาทำการ:</strong> จันทร์-อาทิตย์, 08:00 - 21:00 น.</p>
            </div>

            <h3>ส่งข้อความถึงเรา</h3>
            <?php if (!empty($_SESSION['contact_message'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $_SESSION['contact_message']; unset($_SESSION['contact_message']); ?>
                </div>
            <?php endif; ?>
            <form action="contact.php" method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name">ชื่อ:</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">อีเมล:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subject">หัวข้อ:</label>
                    <input type="text" id="subject" name="subject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="message">ข้อความ:</label>
                    <textarea id="message" name="message" rows="5" class="form-control" required></textarea>
                </div>
                <button type="submit" class="btn-custom">ส่งข้อความ</button>
            </form>
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
