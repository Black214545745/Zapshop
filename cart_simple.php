<?php
session_start();

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: user-login.php");
    exit();
}

// ตรวจสอบว่ามี config.php หรือไม่
if (!file_exists('config.php')) {
    die('Error: config.php not found');
}

include 'config.php';

// ทดสอบการเชื่อมต่อฐานข้อมูล
try {
    $conn = getConnection();
    if (!$conn) {
        die('Error: Cannot connect to database');
    }
    echo "<!-- Database connected successfully -->";
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// ตรวจสอบ session cart
$cart = $_SESSION['cart'] ?? [];
$productIds = array_keys($cart);

echo "<!-- Cart items: " . count($productIds) . " -->";

$products = [];
$total_amount = 0;
$total_items = 0;

if (count($productIds) > 0) {
    try {
        $placeholders = implode(',', array_map(function($i) { return '$' . ($i + 1); }, range(0, count($productIds) - 1)));
        $query = "SELECT id, name, price, image_url, description, current_stock FROM products WHERE id IN ($placeholders)";
        $result = pg_query_params($conn, $query, $productIds);
        
        if ($result) {
            while ($product = pg_fetch_assoc($result)) {
                $products[] = $product;
            }
        }
    } catch (Exception $e) {
        echo "<!-- Database query error: " . $e->getMessage() . " -->";
    }
}

// คำนวณยอดรวม
foreach ($products as $product) {
    $quantity = $cart[$product['id']] ?? 0;
    $total_amount += $product['price'] * $quantity;
    $total_items += $quantity;
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - ZapShop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>ตะกร้าสินค้า (ทดสอบ)</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>ข้อมูลตะกร้า</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>จำนวนสินค้า:</strong> <?php echo $total_items; ?> ชิ้น</p>
                        <p><strong>จำนวนรายการ:</strong> <?php echo count($products); ?> รายการ</p>
                        <p><strong>ยอดรวม:</strong> ฿<?php echo number_format($total_amount, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>ข้อมูลระบบ</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></p>
                        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                        <p><strong>Session Cart:</strong> <?php echo json_encode($cart); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (count($products) > 0): ?>
            <div class="mt-4">
                <h3>รายการสินค้า</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>สินค้า</th>
                                <th>ราคา</th>
                                <th>จำนวน</th>
                                <th>ยอดรวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>฿<?php echo number_format($product['price'], 2); ?></td>
                                    <td><?php echo $cart[$product['id']] ?? 0; ?></td>
                                    <td>฿<?php echo number_format(($product['price'] * ($cart[$product['id']] ?? 0)), 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i> ไม่มีสินค้าในตะกร้า
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">กลับหน้าหลัก</a>
            <a href="cart.php" class="btn btn-secondary">ดูตะกร้าเต็ม</a>
        </div>
    </div>
</body>
</html>
