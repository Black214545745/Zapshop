<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit();
}

echo "<h2>🔍 ทดสอบการแก้ไข Checkout</h2>";

// ตรวจสอบข้อมูล Session
echo "<h3>ข้อมูล Session:</h3>";
echo "<pre>";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User ID Type: " . gettype($_SESSION['user_id']) . "\n";
echo "Cart Data: " . json_encode($_SESSION['cart'] ?? []) . "\n";
echo "</pre>";

// ตรวจสอบโครงสร้างตาราง orders
echo "<h3>โครงสร้างตาราง orders:</h3>";
try {
    $conn = getConnection();
    $query = "SELECT column_name, data_type, is_nullable 
              FROM information_schema.columns 
              WHERE table_name = 'orders' 
              ORDER BY ordinal_position";
    $result = pg_query($conn, $query);
    
    if ($result) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Column Name</th><th>Data Type</th><th>Nullable</th></tr>";
        
        while ($row = pg_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>{$row['column_name']}</td>";
            echo "<td>{$row['data_type']}</td>";
            echo "<td>{$row['is_nullable']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    pg_close($conn);
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// ตรวจสอบข้อมูลผู้ใช้
echo "<h3>ข้อมูลผู้ใช้:</h3>";
try {
    $conn = getConnection();
    $userQuery = "SELECT full_name, email, phone, address FROM user_profiles WHERE user_id = $1";
    $userResult = pg_query_params($conn, $userQuery, [$_SESSION['user_id']]);
    
    if ($userResult && pg_num_rows($userResult) > 0) {
        $userData = pg_fetch_assoc($userResult);
        echo "<pre>";
        print_r($userData);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>ไม่พบข้อมูลผู้ใช้ใน user_profiles</p>";
    }
    pg_close($conn);
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// ตรวจสอบข้อมูลสินค้าในตะกร้า
echo "<h3>ข้อมูลสินค้าในตะกร้า:</h3>";
if (!empty($_SESSION['cart'])) {
    try {
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
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: orange;'>ไม่มีสินค้าในตะกร้า</p>";
}

// ทดสอบการสร้าง Order
echo "<h3>ทดสอบการสร้าง Order:</h3>";
echo "<button onclick='testCreateOrder()'>ทดสอบสร้าง Order</button>";
echo "<div id='test-results'></div>";

echo "<script>
function testCreateOrder() {
    const testResults = document.getElementById('test-results');
    testResults.innerHTML = '<p>กำลังทดสอบ...</p>';
    
    // สร้างข้อมูลทดสอบ
    const testData = {
        user_id: '" . $_SESSION['user_id'] . "',
        fullname: 'ทดสอบ',
        tel: '0812345678',
        email: 'test@example.com',
        address: 'ที่อยู่ทดสอบ',
        grand_total: 1000,
        payment_method: 'promptpay'
    };
    
    // ทดสอบการสร้าง Order
    fetch('test_create_order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(testData)
    })
    .then(response => response.json())
    .then(data => {
        testResults.innerHTML = 
            '<div style=\"background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;\">' +
            '<strong>ผลการทดสอบ:</strong><br>' +
            JSON.stringify(data, null, 2) +
            '</div>';
    })
    .catch(error => {
        testResults.innerHTML = 
            '<div style=\"background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;\">' +
            '<strong>Error:</strong><br>' +
            error.message +
            '</div>';
    });
}
</script>";
?>
