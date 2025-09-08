<?php
session_start();
include 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูลที่รองรับทั้ง MySQLi และ PostgreSQL

if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();
$is_pg_conn = isPostgreSQL();

$product_name = trim($_POST['product_name']);
$price = floatval($_POST['price'] ?: 0); // ใช้ floatval เพื่อให้แน่ใจว่าเป็นตัวเลข
$detail = trim($_POST['detail']);
$category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null; // ใช้ intval

$image_name = $_FILES['profile_image']['name'];
$image_tmp = $_FILES['profile_image']['tmp_name'];
$folder = 'upload_image/';
$image_location = $folder . $image_name;

$query_success = false;

if (empty($_POST['id'])) {
    // เพิ่มสินค้าใหม่
    $sql = "INSERT INTO products (name, price, image_url, description, category_id)
            VALUES ($1, $2, $3, $4, $5)";
    
    // เตรียมและรันคำสั่งสำหรับ PostgreSQL
    if ($is_pg_conn) {
        $stmt_name = "insert_product_" . md5($sql);
        $stmt = pg_prepare($conn, $stmt_name, $sql);
        if ($stmt) {
            $query_success = pg_execute($conn, $stmt_name, [$product_name, $price, $image_name, $detail, $category_id]);
        } else {
            error_log("PostgreSQL prepare error: " . pg_last_error($conn));
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการเตรียมคำสั่งฐานข้อมูล!";
        }
    } else {
        // สำหรับ MySQLi
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdssi", $product_name, $price, $image_name, $detail, $category_id);
        $query_success = $stmt->execute();
    }
} else {
    // อัปเดตสินค้า
    $product_id = intval($_POST['id']);

    // ดึงชื่อรูปภาพเดิม
    $current_image_name = '';
    $select_sql = "SELECT image_url FROM products WHERE id = $1";
    if ($is_pg_conn) {
        $stmt_name_select = "select_product_image_" . md5($select_sql);
        $stmt_select = pg_prepare($conn, $stmt_name_select, $select_sql);
        $result_select = pg_execute($conn, $stmt_name_select, [$product_id]);
        if ($result_select && pg_num_rows($result_select) > 0) {
            $row = pg_fetch_assoc($result_select);
            $current_image_name = $row['image_url'];
        }
    } else {
        $stmt_select = $conn->prepare($select_sql);
        $stmt_select->bind_param("i", $product_id);
        $stmt_select->execute();
        $result_select = $stmt_select->get_result();
        if ($result_select && $result_select->num_rows > 0) {
            $row = $result_select->fetch_assoc();
            $current_image_name = $row['image_url'];
        }
    }

    if (empty($image_name)) {
        $image_name = $current_image_name; // ใช้รูปภาพเดิม
    } else {
        // ลบรูปภาพเก่าถ้ามีรูปภาพใหม่ถูกอัปโหลด
        if (!empty($current_image_name) && file_exists($folder . $current_image_name)) {
            @unlink($folder . $current_image_name);
        }
    }

    $update_sql = "UPDATE products SET 
                    name = $1,
                    price = $2,
                    image_url = $3,
                    description = $4,
                    category_id = $5
                    WHERE id = $6";

    // เตรียมและรันคำสั่งสำหรับ PostgreSQL
    if ($is_pg_conn) {
        $stmt_name_update = "update_product_" . md5($update_sql);
        $stmt_update = pg_prepare($conn, $stmt_name_update, $update_sql);
        if ($stmt_update) {
            $query_success = pg_execute($conn, $stmt_name_update, [$product_name, $price, $image_name, $detail, $category_id, $product_id]);
        } else {
            error_log("PostgreSQL prepare error: " . pg_last_error($conn));
            $_SESSION['message'] = "เกิดข้อผิดพลาดในการเตรียมคำสั่งฐานข้อมูล!";
        }
    } else {
        // สำหรับ MySQLi
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("sdssii", $product_name, $price, $image_name, $detail, $category_id, $product_id);
        $query_success = $stmt_update->execute();
    }
}

// ตรวจสอบว่าการเชื่อมต่อเป็นแบบ PostgreSQL ก่อนที่จะเรียก pg_close
if (isset($conn) && is_resource($conn) && get_resource_type($conn) === 'pgsql link') {
    pg_close($conn); // ปิดการเชื่อมต่อฐานข้อมูล PostgreSQL
} elseif (isset($conn) && $conn instanceof mysqli) {
    $conn->close(); // ปิดการเชื่อมต่อฐานข้อมูล MySQLi
}

if ($query_success) {
    if (!empty($image_tmp) && !empty($image_name)) { // ตรวจสอบว่ามีการอัปโหลดไฟล์ใหม่จริงๆ
        move_uploaded_file($image_tmp, $image_location);
    }
    $_SESSION['message'] = 'Product saved successfully';
    header('Location: index.php'); // หรือหน้าที่แสดงรายการสินค้าสำหรับ Admin
    exit();
} else {
    $_SESSION['message'] = 'Failed to save product. Error: ' . ($is_pg_conn ? pg_last_error($conn) : $conn->error);
    header('Location: index.php'); // หรือหน้าที่แสดงรายการสินค้าสำหรับ Admin
    exit();
}
?>
