<?php
// fetch_products.php
// ไฟล์นี้จะรับพารามิเตอร์ filter, category และ search (ถ้ามี)
// แล้วส่งคืน HTML ของ product cards

// เชื่อมต่อฐานข้อมูล
// ตรวจสอบให้แน่ใจว่า config.php อยู่ใน path ที่ถูกต้อง
// หาก config.php อยู่ในโฟลเดอร์เดียวกันกับ fetch_products.php
// include 'config.php';
// หาก config.php อยู่ในโฟลเดอร์ 'include'
include 'config.php'; // ปรับ path ให้ถูกต้องตามตำแหน่งจริงของ config.php

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

// รับค่าพารามิเตอร์จาก AJAX request
$filter_criteria = isset($_GET['filter']) ? pg_escape_string($conn, $_GET['filter']) : 'all';
// แก้ไขจาก 'category' เป็น 'category_id' เพื่อให้ตรงกับชื่อคอลัมน์ในตาราง products
$category_filter = isset($_GET['category']) ? pg_escape_string($conn, $_GET['category']) : '';
$search_query = isset($_GET['search']) ? pg_escape_string($conn, $_GET['search']) : '';

// กำหนดเงื่อนไข ORDER BY ตาม filter
$order_by = "id DESC"; // ใช้ 'id' ซึ่งเป็น primary key ของคุณแทน product_id ถ้าไม่มี product_id
switch ($filter_criteria) {
    case 'latest':
        // ตรวจสอบว่ามีคอลัมน์ 'created_at' ในตาราง products หรือไม่
        // หากไม่มี ให้ใช้คอลัมน์อื่นที่ระบุเวลาสร้าง เช่น 'id' หรือ 'timestamp_column'
        // สมมติว่ามี created_at หากไม่มี ต้องเพิ่มคอลัมน์นี้หรือเปลี่ยนเป็น id DESC
        $order_by = "id DESC"; // หรือ "created_at DESC" ถ้ามีคอลัมน์นี้
        break;
    case 'price_asc':
        $order_by = "price ASC";
        break;
    case 'popular':
        // หากไม่มีคอลัมน์ views หรือ popularity_score
        // คุณอาจจะต้องคำนวณความนิยมจาก orders_details หรือเพิ่มคอลัมน์ views เข้าไป
        // ในกรณีนี้ จะใช้ 'id' เป็นตัวอย่างไปก่อน
        $order_by = "id DESC"; // หรือ "views DESC" หากมีคอลัมน์นี้
        break;
    case 'all':
    default:
        $order_by = "id DESC"; // ใช้ 'id' แทน product_id
        break;
}

// สร้างเงื่อนไข WHERE
$where_clauses = [];
if (!empty($category_filter)) {
    // แก้ไข 'category' เป็น 'category_id' เพื่อให้ตรงกับชื่อคอลัมน์ในตาราง products
    $where_clauses[] = "category_id = '$category_filter'";
}
if (!empty($search_query)) {
    // ตรวจสอบว่าคุณมีคอลัมน์ 'description' ในตาราง products หรือไม่
    // หากไม่มี ให้ลบส่วน OR description LIKE ออกไป
    $where_clauses[] = "(name LIKE '%$search_query%' OR description LIKE '%$search_query%')"; // ใช้ 'description' ตามตาราง PostgreSQL
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// สร้าง SQL query เต็ม
$sql = "SELECT id, name as product_name, price, image_url as profile_image, description as detail FROM products" . $where_sql . " ORDER BY " . $order_by;
$result = pg_query($conn, $sql);

$html_output = '';
if ($result && pg_num_rows($result) > 0) {
    while ($product = pg_fetch_assoc($result)) {
        // แก้ไข 'image_url' เป็น 'profile_image' เพื่อให้ตรงกับชื่อคอลัมน์ในตาราง products
        // และ 'product_id' เป็น 'id' เพื่อให้ตรงกับ primary key ของคุณ
        $html_output .= '
            <div class="product-card">
                <img src="upload_image/' . htmlspecialchars($product['profile_image']) . '" alt="' . htmlspecialchars($product['product_name']) . '" class="product-image">
                <div class="product-content">
                    <h3 class="product-title">' . htmlspecialchars($product['product_name']) . '</h3>
                    <p class="product-price">' . number_format($product['price'], 2) . ' บาท</p>
                    <p class="product-description">' . htmlspecialchars($product['detail']) . '</p>
                    <button class="add-to-cart" onclick="addToCart(' . htmlspecialchars($product['id']) . ')">
                        <i class="fas fa-cart-plus me-2"></i>
                        เพิ่มลงตะกร้า
                    </button>
                </div>
            </div>';
    }
} else {
    $html_output = '
        <div class="empty-state col-12">
            <i class="fas fa-box-open empty-icon"></i>
            <h4 class="empty-title">ไม่พบสินค้า</h4>
            <p class="empty-text">ลองเลือกตัวกรองอื่น หรือปรับคำค้นหา</p>
        </div>';
}

echo $html_output;

// ตรวจสอบให้แน่ใจว่า $conn ถูกกำหนดค่าก่อนเรียกใช้ close()
if (isset($conn)) {
    pg_close($conn);
}
?>