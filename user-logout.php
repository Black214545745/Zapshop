<?php
session_start();
include 'config.php';

// บันทึก Activity Log ก่อนออกจากระบบ
if (isset($_SESSION['user_id'])) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out successfully', 'users', $_SESSION['user_id']);
}

// ลบข้อมูลทั้งหมดใน session
session_unset();

// ทำลาย session
session_destroy();

// ลบ cookie session (ถ้ามี)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// เปลี่ยนเส้นทางไปที่หน้า index.php
header("Location: index.php");
exit();
?>
