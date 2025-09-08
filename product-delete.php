<?php
session_start();
// ตรวจสอบว่า admin ล็อกอินแล้วหรือยัง
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php"); // ถ้ายังไม่ได้ล็อกอินให้ไปที่หน้า login
    exit();
}
include 'config.php';

if(!empty($_GET['id'])) {
    $product_id = intval($_GET['id']); // ใช้ intval เพื่อความปลอดภัย

    // ดึงชื่อไฟล์รูปภาพเพื่อลบไฟล์
    // ใช้ Prepared Statements สำหรับ PostgreSQL
    $query_product_sql = "SELECT profile_image FROM products WHERE id = $1";
    $stmt_product = pg_prepare($conn, "select_product_image", $query_product_sql);
    $result_product = pg_execute($conn, "select_product_image", [$product_id]);
    $product_data = pg_fetch_assoc($result_product);

    if ($product_data && !empty($product_data['profile_image'])) {
        // ลบไฟล์รูปภาพออกจากเซิร์ฟเวอร์
        @unlink('upload_image/' . $product_data['profile_image']);
    }
    
    // ลบข้อมูลสินค้าออกจากฐานข้อมูล
    // ใช้ Prepared Statements สำหรับ PostgreSQL
    $delete_query = "DELETE FROM products WHERE id = $1";
    $stmt_delete = pg_prepare($conn, "delete_product", $delete_query);
    $query_success = pg_execute($conn, "delete_product", [$product_id]);
    
    // ตรวจสอบว่าการเชื่อมต่อเป็นแบบ PostgreSQL ก่อนที่จะเรียก pg_close
    if (isset($conn) && is_resource($conn) && get_resource_type($conn) === 'pgsql link') {
        pg_close($conn);
    } elseif (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    
    if($query_success) {
        $_SESSION['message'] = 'Product Deleted success';
        header('location: ' . $base_url . '/index.php'); // หรือหน้าที่แสดงรายการสินค้าสำหรับ Admin
        exit();
    } else {
        $_SESSION['message'] = 'Product could not be deleted! Error: ' . pg_last_error($conn);
        header('location: ' . $base_url . '/index.php'); // หรือหน้าที่แสดงรายการสินค้าสำหรับ Admin
        exit();
    }
} else {
    $_SESSION['message'] = 'Product ID is missing!';
    header('location: ' . $base_url . '/index.php'); // หรือหน้าที่แสดงรายการสินค้าสำหรับ Admin
    exit();
}
?>
