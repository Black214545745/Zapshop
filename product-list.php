<?php
// product-list.php
session_start(); // ตรวจสอบให้แน่ใจว่า session_start() ถูกเรียกใช้เสมอ
// ** 1. เชื่อมต่อฐานข้อมูล **
include 'config.php'; // ไฟล์เชื่อมต่อฐานข้อมูลที่รองรับทั้ง MySQLi และ PostgreSQL

// ** 2. รับค่า category และ search จาก URL **
$category_filter_name = '';
$category_filter_id = null;
$search_query = '';
$filter_criteria = 'all'; // Default filter criteria

// เชื่อมต่อฐานข้อมูล
$conn = getConnection();
$is_pg_conn = isPostgreSQL();

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter_name = pg_escape_string($conn, $_GET['category']);
    // ค้นหา category_id จาก category_name
    $sql_get_category_id = "SELECT id FROM categories WHERE name = '$category_filter_name'";
    $result_get_category_id = pg_query($conn, $sql_get_category_id);
    
    if ($result_get_category_id) {
        if (pg_num_rows($result_get_category_id) > 0) {
            $row_cat_id = pg_fetch_assoc($result_get_category_id);
            $category_filter_id = $row_cat_id['id'];
        }
    }
}

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = pg_escape_string($conn, $_GET['search']);
}

// ** 3. สร้าง SQL Query สำหรับดึงสินค้า **
$sql = "SELECT p.id, p.name as product_name, p.price, p.image_url as profile_image, p.description as detail, c.name as category_name,
               COALESCE(SUM(od.quantity), 0) as total_sold
        FROM products p
        JOIN categories c ON p.category_id = c.id
        LEFT JOIN order_details od ON p.id = od.product_id
        LEFT JOIN orders o ON od.order_id = o.id";

$where_clauses = [];

if ($category_filter_id !== null) {
    $where_clauses[] = "p.category_id = '$category_filter_id'";
}
if (!empty($search_query)) {
    // เพิ่มเงื่อนไขค้นหาในชื่อสินค้าและรายละเอียด
    $where_clauses[] = "(p.name ILIKE '%$search_query%' OR p.description ILIKE '%$search_query%' OR c.name ILIKE '%$search_query%')";
    // ใช้ ILIKE สำหรับ PostgreSQL เพื่อให้ค้นหาแบบไม่สนใจตัวพิมพ์เล็ก-ใหญ่
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY p.id, p.name, p.price, p.image_url, p.description, c.name";

// ตั้งค่า ORDER BY เริ่มต้น
$sql .= " ORDER BY p.id DESC";

$result = pg_query($conn, $sql);

$products = [];
if ($result) {
    if (pg_num_rows($result) > 0) {
        while ($row = pg_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
}

// ** 4. ฟังก์ชันสำหรับ AJAX Filter **
if (isset($_POST['ajax_filter'])) {
    $filter_type = $_POST['filter_type'];
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : null;
    $search_term = isset($_POST['search_term']) ? $_POST['search_term'] : '';
    
    $ajax_sql = "SELECT p.id, p.name as product_name, p.price, p.image_url as profile_image, p.description as detail, c.name as category_name,
                         COALESCE(SUM(od.quantity), 0) as total_sold
                  FROM products p
                  JOIN categories c ON p.category_id = c.id
                  LEFT JOIN order_details od ON p.id = od.product_id
                  LEFT JOIN orders o ON od.order_id = o.id";
    
    $ajax_where = [];
    
    if ($category_id) {
        $ajax_where[] = "p.category_id = '$category_id'";
    }
    
    if (!empty($search_term)) {
        $search_term_escaped = pg_escape_string($conn, $search_term);
        $ajax_where[] = "(p.name ILIKE '%$search_term_escaped%' OR p.description ILIKE '%$search_term_escaped%' OR c.name ILIKE '%$search_term_escaped%')";
    }
    
    if (!empty($ajax_where)) {
        $ajax_sql .= " WHERE " . implode(" AND ", $ajax_where);
    }
    
    $ajax_sql .= " GROUP BY p.id, p.name, p.price, p.image_url, p.description, c.name";
    
    // กำหนดการเรียงลำดับตาม filter type
    switch ($filter_type) {
        case 'latest':
            $ajax_sql .= " ORDER BY p.id DESC";
            break;
        case 'price_asc':
            $ajax_sql .= " ORDER BY p.price ASC";
            break;
        case 'price_desc':
            $ajax_sql .= " ORDER BY p.price DESC";
            break;
        case 'popular':
            $ajax_sql .= " ORDER BY total_sold DESC, p.id DESC";
            break;
        default:
            $ajax_sql .= " ORDER BY p.id DESC";
    }
    
    $ajax_result = pg_query($conn, $ajax_sql);
    $ajax_products = [];
    
    if ($ajax_result) {
        if (pg_num_rows($ajax_result) > 0) {
            while ($row = pg_fetch_assoc($ajax_result)) {
                $ajax_products[] = $row;
            }
        }
    }
    
    // ส่งข้อมูลกลับเป็น JSON
    header('Content-Type: application/json');
    echo json_encode($ajax_products);
    exit;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopZone - แหล่งรวมสินค้าคุณภาพ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .product-card {
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            margin-bottom: 25px;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .product-card img {
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .card-body {
            padding: 1.25rem;
        }
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            min-height: 50px;
        }
        .card-text {
            color: #6c757d;
            font-size: 0.9rem;
            min-height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .product-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #e53e3e;
            margin-top: 1rem;
        }
        .btn-add-to-cart {
            background-color: #e53e3e;
            border-color: #e53e3e;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-add-to-cart:hover {
            background-color: #c53030;
            border-color: #c53030;
        }
        .category-badge {
            background-color: #6c757d;
            color: white;
            padding: .3em .6em;
            border-radius: .3rem;
            font-size: .8em;
        }
    </style>
</head>
<body>

<header class="sticky-top bg-danger border-bottom shadow-sm py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-4">
                <!-- สามารถเพิ่มโลโก้หรือชื่อร้านค้าที่นี่ได้ -->
            </div>
            <div class="col-4 text-center">
                <ul class="nav nav-pills justify-content-center">
                    <li class="nav-item">
                        <a href="<?php echo $base_url; ?>/index.php" class="nav-link fs-4 px-4 py-2 text-white fw-bold active"> Home
                        </a>
                    </li>
                    <?php if (isset($_SESSION['admin_username'])): ?>
                    <li class="nav-item ms-3">
                        <a href="<?php echo $base_url; ?>/admin-dashboard.php" class="nav-link fs-4 px-4 py-2 text-white fw-bold">
                            Admin Dashboard
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-4 text-end">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="text-white me-2">สวัสดี, <?php echo $_SESSION['username']; ?></span>
                    <a href="<?php echo $base_url; ?>/user-logout.php" class="btn btn-light btn-sm fw-bold">
                        Logout
                    </a>
                <?php elseif (isset($_SESSION['admin_username'])): ?>
                    <a href="<?php echo $base_url; ?>/admin-logout.php" class="btn btn-light btn-sm fw-bold">
                        Logout (Admin)
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>/user-login.php" class="btn btn-light btn-sm fw-bold me-2">
                        เข้าสู่ระบบ
                    </a>
                    <a href="<?php echo $base_url; ?>/user-register.php" class="btn btn-outline-light btn-sm fw-bold">
                        ลงทะเบียน
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<div class="container mt-5">
    <h1 class="text-center mb-5">สินค้าทั้งหมด</h1>
    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <form action="product-list.php" method="GET" class="input-group">
                <input type="text" name="search" class="form-control" placeholder="ค้นหาสินค้าหรือหมวดหมู่..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i> ค้นหา</button>
            </form>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <label for="categoryFilter" class="form-label">กรองตามหมวดหมู่:</label>
            <select id="categoryFilter" class="form-select">
                <option value="">ทั้งหมด</option>
                <?php
                // ดึงรายการหมวดหมู่ทั้งหมดสำหรับ dropdown
                $all_categories = [];
                $sql_all_cat = "SELECT id, name as category_name FROM categories ORDER BY name ASC";
                $result_all_cat = pg_query($conn, $sql_all_cat);
                
                if ($result_all_cat) {
                    while ($row_cat = pg_fetch_assoc($result_all_cat)) {
                        $all_categories[] = $row_cat;
                    }
                }

                foreach ($all_categories as $cat) {
                    $selected = ($category_filter_id == $cat['id']) ? 'selected' : '';
                    echo "<option value='{$cat['id']}' {$selected}>" . htmlspecialchars($cat['category_name']) . "</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="row">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $row): ?>
                <div class="col-md-4 col-sm-6 d-flex align-items-stretch">
                    <div class="card product-card w-100">
                        <?php 
                        $image_path = '';
                        $folder = 'upload_image/';
                        if (!empty($row['profile_image'])) {
                            if (strpos($row['profile_image'], '[1]') !== false) {
                                $image_path = 'https://placehold.co/200x200/cccccc/333333?text=Image+Missing';
                            } else {
                                $image_path = $base_url . '/' . $folder . htmlspecialchars($row['profile_image']);
                            }
                        } else {
                            $image_path = 'https://placehold.co/200x200/cccccc/333333?text=No+Image';
                        }
                        ?>
                        <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['product_name']); ?></h5>
                            <p class="card-text flex-grow-1"><?php echo htmlspecialchars($row['detail'] ?: 'ไม่มีรายละเอียด'); ?></p>
                            <div class="mb-2">
                                <span class="category-badge"><?php echo htmlspecialchars($row['category_name'] ?: 'ไม่ระบุ'); ?></span>
                            </div>
                            <p class="product-price">฿<?php echo number_format($row['price'], 2); ?></p>
                            <a href="<?php echo $base_url; ?>/cart-add.php?id=<?php echo $row['id']; ?>" class="btn btn-add-to-cart mt-auto"><i class="fas fa-shopping-cart me-2"></i>เพิ่มลงตะกร้า</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-center">ไม่พบสินค้าในระบบ</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('categoryFilter').addEventListener('change', function() {
        var categoryId = this.value;
        var currentSearch = document.querySelector('input[name="search"]').value;
        var url = 'product-list.php';
        var params = [];

        if (categoryId) {
            params.push('category_id=' + categoryId);
        }
        if (currentSearch) {
            params.push('search=' + encodeURIComponent(currentSearch));
        }

        if (params.length > 0) {
            url += '?' + params.join('&');
        }
        window.location.href = url;
    });
</script>
</body>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล PostgreSQL
if (isset($conn)) {
    pg_close($conn);
}
?>
