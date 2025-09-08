<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

echo "<h2>🔍 ตรวจสอบ Backend Sync</h2>";
echo "<h3>ข้อมูล Session Cart:</h3>";
echo "<pre>";
print_r($_SESSION['cart'] ?? []);
echo "</pre>";

echo "<h3>ข้อมูลใน Database:</h3>";
if (!empty($_SESSION['cart'])) {
    $conn = getConnection();
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_map(function($i) { return '$' . ($i + 1); }, range(0, count($productIds) - 1)));
    $query = "SELECT id, name, price, current_stock FROM products WHERE id IN ($placeholders)";
    $result = pg_query_params($conn, $query, $productIds);
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>ชื่อสินค้า</th><th>ราคา</th><th>สต็อก</th><th>จำนวนในตะกร้า</th></tr>";
        
        while ($product = pg_fetch_assoc($result)) {
            $cartQty = $_SESSION['cart'][$product['id']] ?? 0;
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>฿{$product['price']}</td>";
            echo "<td>{$product['current_stock']}</td>";
            echo "<td>{$cartQty}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    pg_close($conn);
} else {
    echo "<p>ไม่มีสินค้าในตะกร้า</p>";
}

echo "<h3>การทดสอบ API:</h3>";
echo "<button onclick='testCartUpdate()'>ทดสอบ cart-update.php</button>";
echo "<button onclick='testCartDelete()'>ทดสอบ cart-delete.php</button>";
echo "<div id='test-results'></div>";

echo "<script>
function testCartUpdate() {
    fetch('cart-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=1&quantity=5'
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('test-results').innerHTML = 
            '<div style=\"background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;\">' +
            '<strong>cart-update.php Result:</strong><br>' +
            JSON.stringify(data, null, 2) +
            '</div>';
    })
    .catch(error => {
        document.getElementById('test-results').innerHTML = 
            '<div style=\"background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;\">' +
            '<strong>Error:</strong><br>' +
            error.message +
            '</div>';
    });
}

function testCartDelete() {
    fetch('cart-delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=999'
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('test-results').innerHTML = 
            '<div style=\"background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;\">' +
            '<strong>cart-delete.php Result:</strong><br>' +
            JSON.stringify(data, null, 2) +
            '</div>';
    })
    .catch(error => {
        document.getElementById('test-results').innerHTML = 
            '<div style=\"background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;\">' +
            '<strong>Error:</strong><br>' +
            error.message +
            '</div>';
    });
}
</script>";
?>
