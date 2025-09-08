<?php
session_start();

// บันทึก Activity Log ก่อนออกจากระบบ
if (isset($_SESSION['customer_id'])) {
    include 'config.php';
    logActivity($_SESSION['customer_id'], 'logout', 'Customer logged out', 'customers', $_SESSION['customer_id']);
}

// ลบ session ของลูกค้า
unset($_SESSION['customer_id']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_logged_in']);

// ลบ session ทั้งหมด
session_destroy();

// เริ่ม session ใหม่
session_start();
$_SESSION['message'] = "ออกจากระบบเรียบร้อยแล้ว";

// กลับไปหน้าแรก
header("Location: index.php");
exit();
?>
