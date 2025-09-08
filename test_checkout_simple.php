<?php
session_start();
include 'config.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือไม่
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>กรุณาเข้าสู่ระบบก่อน</p>";
    exit();
}

// สร้างข้อมูลทดสอบในตะกร้า
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        '1' => 2,  // สินค้า ID 1 จำนวน 2 ชิ้น
        '2' => 1   // สินค้า ID 2 จำนวน 1 ชิ้น
    ];
}

echo "<h2>🧪 ทดสอบการ Checkout</h2>";

echo "<h3>ข้อมูล Session:</h3>";
echo "<pre>";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User ID Type: " . gettype($_SESSION['user_id']) . "\n";
echo "Cart: " . json_encode($_SESSION['cart']) . "\n";
echo "</pre>";

echo "<h3>ทดสอบการสร้าง Order:</h3>";
echo "<button onclick='testCheckout()'>ทดสอบ Checkout</button>";
echo "<div id='test-results'></div>";

echo "<script>
function testCheckout() {
    const testResults = document.getElementById('test-results');
    testResults.innerHTML = '<p>กำลังทดสอบ...</p>';
    
    fetch('checkout_fixed.php')
    .then(response => {
        if (response.redirected) {
            testResults.innerHTML = 
                '<div style=\"background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;\">' +
                '<strong>✅ Checkout สำเร็จ!</strong><br>' +
                'Redirected to: ' + response.url +
                '</div>';
        } else {
            return response.text();
        }
    })
    .then(data => {
        if (data) {
            testResults.innerHTML = 
                '<div style=\"background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;\">' +
                '<strong>❌ Checkout ล้มเหลว:</strong><br>' +
                data +
                '</div>';
        }
    })
    .catch(error => {
        testResults.innerHTML = 
            '<div style=\"background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;\">' +
            '<strong>❌ Error:</strong><br>' +
            error.message +
            '</div>';
    });
}
</script>";
?>
