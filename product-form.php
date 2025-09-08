<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เป็นแอดมินหรือไม่
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

$product = [
    'id' => '',
    'product_name' => '',
    'price' => '',
    'detail' => '',
    'profile_image' => '',
    'category_id' => ''
];

// ดึงข้อมูลสินค้า
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']); // ใช้ intval เพื่อความปลอดภัย
    
    // ใช้ Prepared Statements สำหรับ PostgreSQL
    $query_str = "SELECT id, name as product_name, price, image_url as profile_image, description as detail, category_id FROM products WHERE id = $1";
    $stmt = pg_prepare($conn, "get_product_by_id", $query_str);
    $result = pg_execute($conn, "get_product_by_id", [$id]);

    if ($result && pg_num_rows($result) > 0) {
        $product = pg_fetch_assoc($result);
    }
}

// ดึงรายการหมวดหมู่ทั้งหมด
$categories = [];
// ใช้ pg_query แทน mysqli_query
$cat_query = pg_query($conn, "SELECT id, name as category_name FROM categories ORDER BY id ASC"); // เพิ่ม ORDER BY เพื่อให้เรียงลำดับ
if ($cat_query) {
    while ($row = pg_fetch_assoc($cat_query)) {
        $categories[] = $row;
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($conn)) {
    pg_close($conn);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ฟอร์มเพิ่ม/แก้ไขสินค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2><?php echo ($product['id']) ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า'; ?></h2>
    <form action="product-save.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">

        <div class="mb-3">
            <label class="form-label">ชื่อสินค้า</label>
            <input type="text" name="product_name" class="form-control" required value="<?php echo htmlspecialchars($product['product_name']); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">ราคา</label>
            <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($product['price']); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">รายละเอียดสินค้า</label>
            <textarea name="detail" class="form-control" rows="4"><?php echo htmlspecialchars($product['detail']); ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">หมวดหมู่</label>
            <select name="category_id" class="form-select" required>
                <option value="">-- เลือกหมวดหมู่ --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['id']); ?>" <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">รูปภาพสินค้า</label>
            <?php if (!empty($product['profile_image'])): ?>
                <div class="mb-2">
                    <img src="upload_image/<?php echo htmlspecialchars($product['profile_image']); ?>" width="150" alt="Product Image">
                </div>
            <?php endif; ?>
            <input type="file" name="profile_image" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">บันทึกสินค้า</button>
        <a href="index.php" class="btn btn-secondary">กลับ</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
