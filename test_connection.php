<?php
// ทดสอบการเชื่อมต่อฐานข้อมูล MySQL
echo "<h2>ทดสอบการเชื่อมต่อฐานข้อมูล MySQL</h2>";

// ตั้งค่าการเชื่อมต่อ
$host = 'localhost';
$port = '3306';
$dbname = 'zapstock_db';
$user = 'root';
$password = '';

try {
    // สร้างการเชื่อมต่อ
    $conn = new mysqli($host, $user, $password, $dbname, $port);
    
    // ตรวจสอบการเชื่อมต่อ
    if ($conn->connect_error) {
        die("<p style='color: red;'>❌ การเชื่อมต่อล้มเหลว: " . $conn->connect_error . "</p>");
    }
    
    echo "<p style='color: green;'>✅ การเชื่อมต่อสำเร็จ!</p>";
    echo "<p>Server Info: " . $conn->server_info . "</p>";
    echo "<p>Host Info: " . $conn->host_info . "</p>";
    
    // ทดสอบการ query
    $result = $conn->query("SELECT COUNT(*) as total FROM products");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>จำนวนสินค้าในฐานข้อมูล: " . $row['total'] . " รายการ</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ ไม่สามารถ query ตาราง products ได้ (อาจยังไม่มีตาราง)</p>";
    }
    
    // ตรวจสอบตารางที่มีอยู่
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "<h3>ตารางที่มีอยู่ในฐานข้อมูล:</h3>";
        echo "<ul>";
        while ($row = $result->fetch_array()) {
            echo "<li>" . $row[0] . "</li>";
        }
        echo "</ul>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>คำแนะนำ:</h3>";
echo "<ol>";
echo "<li>หากการเชื่อมต่อล้มเหลว ให้ตรวจสอบว่า MySQL เปิดใช้งานใน XAMPP Control Panel</li>";
echo "<li>หากไม่มีตาราง ให้นำเข้าไฟล์ database_setup.sql ใน phpMyAdmin</li>";
echo "<li>หากยังไม่มีฐานข้อมูล ให้สร้างฐานข้อมูลชื่อ 'zapstock_db' ใน phpMyAdmin</li>";
echo "</ol>";

echo "<p><a href='http://localhost/phpmyadmin' target='_blank'>เปิด phpMyAdmin</a></p>";
?>

