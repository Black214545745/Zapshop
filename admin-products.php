<?php
session_start();
if (!isset($_SESSION['admin_username'])) {
    header("Location: admin-login.php");
    exit();
}

include 'config.php';

// กำหนด base_url สำหรับเมนู
$base_url = '';

include 'include/admin-menu.php';

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();

// กำหนดโฟลเดอร์สำหรับอัปโหลดรูปภาพ
$folder = "upload_image/";

$result = null;
$edit_mode = false;
$search_query = "";

// ดึงข้อมูลหมวดหมู่ทั้งหมด
$categories = [];
$sql_categories = "SELECT id, name as category_name FROM categories ORDER BY name ASC";
$result_categories = pg_query($conn, $sql_categories);
if ($result_categories && pg_num_rows($result_categories) > 0) {
    while ($row = pg_fetch_assoc($result_categories)) {
        $categories[] = $row;
    }
} else {
    // ถ้าไม่มีหมวดหมู่ ให้เพิ่มหมวดหมู่ตัวอย่าง
    $default_categories = [
        ['ขนม', 'ขนมและของหวาน'],
        ['เครื่องดื่ม', 'เครื่องดื่มต่างๆ'],
        ['ของใช้', 'ของใช้ในชีวิตประจำวัน']
    ];
    
    foreach ($default_categories as $cat) {
        $sql_insert = "INSERT INTO categories (name, description) VALUES ($1, $2)";
        pg_query_params($conn, $sql_insert, $cat);
    }
    
    // ดึงข้อมูลหมวดหมู่ใหม่
    $result_categories = pg_query($conn, $sql_categories);
    if ($result_categories && pg_num_rows($result_categories) > 0) {
        while ($row = pg_fetch_assoc($result_categories)) {
            $categories[] = $row;
        }
    }
}

// จัดการการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['product_name']) && isset($_POST['price']) && isset($_POST['detail']) && isset($_POST['category_id'])) {
        $product_name = $_POST['product_name'];
        $price = $_POST['price'];
        $current_stock = isset($_POST['current_stock']) ? (int)$_POST['current_stock'] : 0;
        $min_stock_quantity = isset($_POST['min_stock_quantity']) ? (int)$_POST['min_stock_quantity'] : 5;
        $detail = $_POST['detail'];
        $category_id = $_POST['category_id'];
        $product_id = isset($_POST['id']) ? $_POST['id'] : null;

        $uploadOk = 1;
        $file_name_to_save = "";

        // จัดการอัปโหลดรูปภาพ
        if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == UPLOAD_ERR_OK) {
            $target_file = $folder . basename($_FILES["profile_image"]["name"]);
            $imageFileType = strtolower(pathinfo(pathinfo($target_file, PATHINFO_BASENAME), PATHINFO_EXTENSION));
            $file_name_to_save = basename($_FILES["profile_image"]["name"]);

            $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
            if ($check === false) {
                $_SESSION['message'] = "File is not an image.";
                $uploadOk = 0;
            }

            if ($_FILES["profile_image"]["size"] > 500000) {
                $_SESSION['message'] = "Sorry, your file is too large.";
                $uploadOk = 0;
            }

            $allowed_extensions = array("jpg", "jpeg", "png", "gif");
            if (!in_array($imageFileType, $allowed_extensions)) {
                $_SESSION['message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }

            if ($uploadOk == 1) {
                if (!move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $_SESSION['message'] = "Sorry, there was an error uploading your file.";
                    $uploadOk = 0;
                }
            }
        }

        if ($uploadOk == 1) {
            // Debug: แสดงข้อมูลที่ได้รับ
            error_log("Debug - product_name: " . $product_name);
            error_log("Debug - price: " . $price);
            error_log("Debug - current_stock: " . $current_stock);
            error_log("Debug - min_stock_quantity: " . $min_stock_quantity);
            error_log("Debug - category_id: " . $category_id);
            error_log("Debug - product_id: " . ($product_id ?: 'null'));
            
            if ($product_id) {
                // อัปเดตสินค้า
                    $sql_select_old_image = "SELECT image_url FROM products WHERE id = $1";
    $result_select = pg_query_params($conn, $sql_select_old_image, [$product_id]);
    $old_image_name = '';
    if ($result_select && pg_num_rows($result_select) > 0) {
        $row = pg_fetch_assoc($result_select);
        $old_image_name = $row['image_url'];
    }

                $final_image_name = ($file_name_to_save != "") ? $file_name_to_save : $old_image_name;

                $sql = "UPDATE products SET name = $1, price = $2, image_url = $3, description = $4, category_id = $5, current_stock = $6, min_stock_quantity = $7 WHERE id = $8";
                $result = pg_query_params($conn, $sql, [$product_name, $price, $final_image_name, $detail, $category_id, $current_stock, $min_stock_quantity, $product_id]);

                if ($result) {
                    if ($file_name_to_save != "" && !empty($old_image_name) && file_exists($folder . $old_image_name) && ($file_name_to_save != $old_image_name)) {
                        @unlink($folder . $old_image_name);
                    }
                    $_SESSION['message'] = "Product updated successfully!";
                } else {
                    $_SESSION['message'] = "Error updating product: " . pg_last_error($conn);
                }
            } else {
                // เพิ่มสินค้าใหม่
                $sql = "INSERT INTO products (name, price, image_url, description, category_id, current_stock, min_stock_quantity) VALUES ($1, $2, $3, $4, $5, $6, $7)";
                $result = pg_query_params($conn, $sql, [$product_name, $price, $file_name_to_save, $detail, $category_id, $current_stock, $min_stock_quantity]);

                if ($result) {
                    $_SESSION['message'] = "เพิ่มสินค้าสำเร็จ!";
                } else {
                    $error_msg = pg_last_error($conn);
                    error_log("Error adding product: " . $error_msg);
                    $_SESSION['message'] = "เกิดข้อผิดพลาดในการเพิ่มสินค้า: " . $error_msg;
                }
            }
        }
        header("Location: admin-products.php");
        exit();
    }
}

// จัดการการลบสินค้า
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $sql_select_image = "SELECT image_url FROM products WHERE id = $1";
    $result_select = pg_query_params($conn, $sql_select_image, [$delete_id]);
    $image_to_delete = '';
    if ($result_select && pg_num_rows($result_select) > 0) {
        $row = pg_fetch_assoc($result_select);
        $image_to_delete = $row['image_url'];
    }

    $sql_delete = "DELETE FROM products WHERE id = $1";
    $result_delete = pg_query_params($conn, $sql_delete, [$delete_id]);

    if ($result_delete) {
        if (!empty($image_to_delete) && file_exists($folder . $image_to_delete)) {
            @unlink($folder . $image_to_delete);
        }
        $_SESSION['message'] = "Product deleted successfully!";
    } else {
        $_SESSION['message'] = "Error deleting product: " . pg_last_error($conn);
    }
    header("Location: admin-products.php");
    exit();
}

// จัดการการแก้ไขสินค้า
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $sql = "SELECT id, name as product_name, price, current_stock, min_stock_quantity, image_url as profile_image, description as detail, category_id FROM products WHERE id = $1";
    $result_edit = pg_query_params($conn, $sql, [$edit_id]);
    if ($result_edit && pg_num_rows($result_edit) > 0) {
        $result = pg_fetch_assoc($result_edit);
        $edit_mode = true;
    }
}

// ดึงข้อมูลสินค้าทั้งหมด
$sql_products = "SELECT p.id, p.name as product_name, p.price, p.current_stock, p.min_stock_quantity, p.image_url as profile_image, p.description as detail, c.name as category_name 
                 FROM products p
                 LEFT JOIN categories c ON p.category_id = c.id";

if (isset($_POST['search']) && !empty(trim($_POST['search']))) {
    $search_query = trim($_POST['search']);
    $sql_products .= " WHERE p.name LIKE $1 OR p.description LIKE $2";
    $search_param = "%" . $search_query . "%";
    $result_products = pg_query_params($conn, $sql_products, [$search_param, $search_param]);
} else {
    $sql_products .= " ORDER BY p.id DESC";
    $result_products = pg_query($conn, $sql_products);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า | Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding-top: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: none;
        }
        .card-header {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #e53e3e;
            border-color: #e53e3e;
        }
        .btn-primary:hover {
            background-color: #c53030;
            border-color: #c53030;
        }
        .btn-success {
            background-color: #48bb78;
            border-color: #48bb78;
        }
        .btn-success:hover {
            background-color: #38a169;
            border-color: #38a169;
        }
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #333;
        }
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #e0a800;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #c82333;
        }
        .table img {
            max-width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
            border-radius: 10px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="container-xl py-4">

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- สถิติสต็อก -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-boxes fa-2x mb-2"></i>
                    <h5>สต็อกปกติ</h5>
                    <?php
                    $sql_normal = "SELECT COUNT(*) as count FROM products WHERE current_stock > min_stock_quantity";
                    $result_normal = pg_query($conn, $sql_normal);
                    $normal_count = pg_fetch_assoc($result_normal)['count'];
                    ?>
                    <h3><?php echo $normal_count; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h5>สต็อกต่ำ</h5>
                    <?php
                    $sql_low = "SELECT COUNT(*) as count FROM products WHERE current_stock > 0 AND current_stock <= min_stock_quantity";
                    $result_low = pg_query($conn, $sql_low);
                    $low_count = pg_fetch_assoc($result_low)['count'];
                    ?>
                    <h3><?php echo $low_count; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                    <h5>หมดสต็อก</h5>
                    <?php
                    $sql_out = "SELECT COUNT(*) as count FROM products WHERE current_stock = 0";
                    $result_out = pg_query($conn, $sql_out);
                    $out_count = pg_fetch_assoc($result_out)['count'];
                    ?>
                    <h3><?php echo $out_count; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-2x mb-2"></i>
                    <h5>สินค้าทั้งหมด</h5>
                    <?php
                    $sql_total = "SELECT COUNT(*) as count FROM products";
                    $result_total = pg_query($conn, $sql_total);
                    $total_count = pg_fetch_assoc($result_total)['count'];
                    ?>
                    <h3><?php echo $total_count; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- ฟอร์มเพิ่ม/แก้ไขสินค้า -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle me-2"></i><?php echo $edit_mode ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่'; ?>
        </div>
        <div class="card-body">
            <form action="admin-products.php" method="POST" enctype="multipart/form-data">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?php echo $result['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="product_name" class="form-label">ชื่อสินค้า</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($result['product_name']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="price" class="form-label">ราคา (฿)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($result['price']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="current_stock" class="form-label">จำนวนสินค้า</label>
                            <input type="number" class="form-control" id="current_stock" name="current_stock" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($result['current_stock']) : '0'; ?>" 
                                   min="0" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="min_stock_quantity" class="form-label">จำนวนขั้นต่ำ (เตือนเมื่อต่ำกว่า)</label>
                            <input type="number" class="form-control" id="min_stock_quantity" name="min_stock_quantity" 
                                   value="<?php echo $edit_mode ? htmlspecialchars($result['min_stock_quantity']) : '5'; ?>" 
                                   min="0" required>
                            <small class="text-muted">ระบบจะแจ้งเตือนเมื่อจำนวนสินค้าต่ำกว่าค่านี้</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="detail" class="form-label">รายละเอียด</label>
                    <textarea class="form-control" id="detail" name="detail" rows="3"><?php echo $edit_mode ? htmlspecialchars($result['detail']) : ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">หมวดหมู่สินค้า</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">เลือกหมวดหมู่</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo ($edit_mode && $result['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="profile_image" class="form-label">รูปภาพสินค้า</label>
                            <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                            <?php if ($edit_mode && !empty($result['profile_image'])): ?>
                                <?php 
                                $current_image_path = $result['profile_image'];
                                if (strpos($current_image_path, 'http') === 0) {
                                    $current_image_src = $current_image_path;
                                } else {
                                    $current_image_src = $folder . htmlspecialchars($current_image_path);
                                }
                                ?>
                                <small class="text-muted mt-2 d-block">รูปภาพปัจจุบัน: 
                                    <a href="<?php echo $current_image_src; ?>" target="_blank">
                                        <?php echo htmlspecialchars($result['profile_image']); ?>
                                    </a>
                                </small>
                                <img src="<?php echo $current_image_src; ?>" 
                                     alt="Current Product Image" class="img-thumbnail mt-2" style="max-width: 150px;"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <small class="text-muted" style="display:none;">รูปภาพเสียหาย</small>
                            <?php elseif ($edit_mode && empty($result['profile_image'])): ?>
                                <small class="text-muted mt-2 d-block">ไม่มีรูปภาพปัจจุบัน</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-start gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i><?php echo $edit_mode ? 'บันทึกการแก้ไข' : 'เพิ่มสินค้า'; ?>
                    </button>
                    <?php if ($edit_mode): ?>
                        <a href="admin-products.php" class="btn btn-secondary">
                            <i class="fas fa-times-circle me-2"></i>ยกเลิก
                        </a>
                    <?php else: ?>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-redo me-2"></i>ล้างฟอร์ม
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ตัวกรองและเรียงลำดับ -->
    <div class="card shadow mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <form class="d-flex" method="POST" action="admin-products.php">
                        <input class="form-control me-2" type="search" placeholder="ค้นหาสินค้า..." 
                               name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <button class="btn btn-light" type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <label class="form-label me-2 mb-0">เรียงลำดับ:</label>
                        <select class="form-select me-2" id="sortSelect" onchange="sortProducts()">
                            <option value="name_asc">ชื่อสินค้า A-Z</option>
                            <option value="name_desc">ชื่อสินค้า Z-A</option>
                            <option value="price_asc">ราคา 1-1000</option>
                            <option value="price_desc">ราคา 1000-1</option>
                            <option value="stock_asc">จำนวนสินค้า น้อย-มาก</option>
                            <option value="stock_desc">จำนวนสินค้า มาก-น้อย</option>
                            <option value="category_asc">หมวดหมู่ ก-ฮ</option>
                            <option value="category_desc">หมวดหมู่ ฮ-ก</option>
                        </select>
                        <button class="btn btn-outline-secondary" onclick="resetSort()">
                            <i class="fas fa-undo"></i> รีเซ็ต
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ตารางสินค้า -->
    <div class="card shadow">
        <div class="card-header">
            <span><i class="fas fa-list me-2"></i>รายการสินค้า</span>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-striped align-middle">
                <thead class="table-danger">
                    <tr>
                        <th>#</th>
                        <th>รูป</th>
                        <th>ชื่อสินค้า</th>
                        <th>ราคา</th>
                        <th>จำนวนสินค้า</th>
                        <th>สถานะสต็อก</th>
                        <th>หมวดหมู่</th>
                        <th>รายละเอียด</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_products && pg_num_rows($result_products) > 0): ?>
                        <?php $i = 1; while ($row = pg_fetch_assoc($result_products)): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td>
                                    <?php if (!empty($row['profile_image'])): ?>
                                        <?php 
                                        $image_path = $row['profile_image'];
                                        // ตรวจสอบว่าเป็น URL หรือ path ภายใน
                                        if (strpos($image_path, 'http') === 0) {
                                            // เป็น URL ภายนอก
                                            $image_src = $image_path;
                                        } else {
                                            // เป็น path ภายใน
                                            $image_src = $folder . htmlspecialchars($image_path);
                                        }
                                        ?>
                                        <img src="<?php echo $image_src; ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                        <span class="text-muted" style="display:none;">รูปเสียหาย</span>
                                    <?php else: ?>
                                        <span class="text-muted">ไม่มีรูป</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td>฿<?php echo number_format($row['price'], 2); ?></td>
                                <td>
                                    <span class="fw-bold <?php echo $row['current_stock'] <= $row['min_stock_quantity'] ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo number_format($row['current_stock']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['current_stock'] <= 0): ?>
                                        <span class="badge bg-danger">หมดสต็อก</span>
                                    <?php elseif ($row['current_stock'] <= $row['min_stock_quantity']): ?>
                                        <span class="badge bg-warning">สต็อกต่ำ</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">ปกติ</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['category_name'] ?: 'ไม่ระบุ'); ?></td>
                                <td><?php echo htmlspecialchars($row['detail']); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="admin-products.php?edit_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-warning btn-sm" title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm" 
                                                onclick="updateStock('<?php echo $row['id']; ?>', 'add')" 
                                                title="เพิ่มสต็อก">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm" 
                                                onclick="updateStock('<?php echo $row['id']; ?>', 'subtract')" 
                                                title="ลดสต็อก">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <a href="admin-products.php?delete_id=<?php echo $row['id']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบสินค้า?');" 
                                           title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-box-open fa-3x mb-3 d-block"></i>
                                ไม่พบสินค้า
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
function updateStock(productId, action) {
    const quantity = prompt(`กรุณาใส่จำนวนที่ต้องการ${action === 'add' ? 'เพิ่ม' : 'ลด'}:`);
    
    if (quantity === null || quantity === '') {
        return;
    }
    
    let qty = parseInt(quantity);
    if (isNaN(qty) || qty <= 0) {
        alert('กรุณาใส่จำนวนที่ถูกต้อง');
        return;
    }
    
    if (action === 'subtract') {
        qty = -qty; // ทำให้เป็นลบสำหรับการลด
    }
    
    console.log('Sending stock update:', {productId, qty, action});
    
    // ส่งข้อมูลไปยัง server
    fetch('update_stock.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${encodeURIComponent(productId)}&quantity=${qty}`
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text(); // ใช้ text() ก่อนเพื่อดู raw response
    })
    .then(text => {
        console.log('Raw response:', text);
        
        // ลบ HTML error tags ออกก่อน
        const cleanText = text.replace(/<br\s*\/?>/gi, '').replace(/<[^>]*>/g, '');
        
        try {
            const data = JSON.parse(cleanText);
            if (data.success) {
                alert('อัปเดตสต็อกสำเร็จ!');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด: ' + data.message);
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            // ลองหา JSON ใน response
            const jsonMatch = text.match(/\{.*\}/);
            if (jsonMatch) {
                try {
                    const data = JSON.parse(jsonMatch[0]);
                    if (data.success) {
                        alert('อัปเดตสต็อกสำเร็จ!');
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                } catch (e2) {
                    alert('เกิดข้อผิดพลาดในการประมวลผลข้อมูล');
                }
            } else {
                alert('เกิดข้อผิดพลาดในการประมวลผลข้อมูล');
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('เกิดข้อผิดพลาดในการอัปเดตสต็อก: ' + error.message);
    });
}

// ฟังก์ชันเรียงลำดับสินค้า
function sortProducts() {
    const sortSelect = document.getElementById('sortSelect');
    const sortValue = sortSelect.value;
    const table = document.querySelector('.table tbody');
    const rows = Array.from(table.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aValue, bValue;
        
        switch(sortValue) {
            case 'name_asc':
                aValue = a.cells[2].textContent.trim().toLowerCase();
                bValue = b.cells[2].textContent.trim().toLowerCase();
                return aValue.localeCompare(bValue, 'th');
                
            case 'name_desc':
                aValue = a.cells[2].textContent.trim().toLowerCase();
                bValue = b.cells[2].textContent.trim().toLowerCase();
                return bValue.localeCompare(aValue, 'th');
                
            case 'price_asc':
                aValue = parseFloat(a.cells[3].textContent.replace(/[฿,]/g, ''));
                bValue = parseFloat(b.cells[3].textContent.replace(/[฿,]/g, ''));
                return aValue - bValue;
                
            case 'price_desc':
                aValue = parseFloat(a.cells[3].textContent.replace(/[฿,]/g, ''));
                bValue = parseFloat(b.cells[3].textContent.replace(/[฿,]/g, ''));
                return bValue - aValue;
                
            case 'stock_asc':
                aValue = parseInt(a.cells[4].textContent.trim());
                bValue = parseInt(b.cells[4].textContent.trim());
                return aValue - bValue;
                
            case 'stock_desc':
                aValue = parseInt(a.cells[4].textContent.trim());
                bValue = parseInt(b.cells[4].textContent.trim());
                return bValue - aValue;
                
            case 'category_asc':
                aValue = a.cells[6].textContent.trim().toLowerCase();
                bValue = b.cells[6].textContent.trim().toLowerCase();
                return aValue.localeCompare(bValue, 'th');
                
            case 'category_desc':
                aValue = a.cells[6].textContent.trim().toLowerCase();
                bValue = b.cells[6].textContent.trim().toLowerCase();
                return bValue.localeCompare(aValue, 'th');
                
            default:
                return 0;
        }
    });
    
    // ลบแถวเก่าออก
    rows.forEach(row => row.remove());
    
    // เพิ่มแถวใหม่ตามลำดับที่เรียงแล้ว
    rows.forEach((row, index) => {
        row.cells[0].textContent = index + 1; // อัปเดตหมายเลข
        table.appendChild(row);
    });
}

// ฟังก์ชันรีเซ็ตการเรียงลำดับ
function resetSort() {
    document.getElementById('sortSelect').value = 'name_asc';
    location.reload(); // รีโหลดหน้าเพื่อกลับไปลำดับเดิม
}
</script>
</body>
</html>

<?php
pg_close($conn);
?> 