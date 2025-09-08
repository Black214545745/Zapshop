<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}
include 'config.php';

// ตรวจสอบว่ามีค่า id ส่งมาใน URL หรือไม่
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin-users.php");
    exit();
}

$id = $_GET['id'];

// ลบผู้ใช้จากฐานข้อมูล
$sql = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: admin-users.php?msg=deleted");
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error;
}

$conn->close();
?>