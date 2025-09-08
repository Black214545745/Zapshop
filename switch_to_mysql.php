<?php
echo "<h2>เปลี่ยนไปใช้ MySQL</h2>";

echo "<p>หาก PostgreSQL extension ไม่สามารถเปิดใช้งานได้ คุณสามารถใช้ MySQL แทนได้</p>";

echo "<h3>ขั้นตอนการเปลี่ยนไปใช้ MySQL:</h3>";
echo "<ol>";
echo "<li>เปิดไฟล์ config.php</li>";
echo "<li>เปลี่ยนการเชื่อมต่อจาก PostgreSQL เป็น MySQL</li>";
echo "<li>สร้างฐานข้อมูล MySQL ใน phpMyAdmin</li>";
echo "<li>รันไฟล์ database_setup.sql</li>";
echo "</ol>";

echo "<h3>ข้อมูลการเชื่อมต่อ MySQL:</h3>";
echo "<ul>";
echo "<li>Host: localhost</li>";
echo "<li>Port: 3306</li>";
echo "<li>Database: zapstock_db</li>";
echo "<li>User: root</li>";
echo "<li>Password: (ว่าง)</li>";
echo "</ul>";

echo "<h3>ลิงก์ที่มีประโยชน์:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost/phpmyadmin' target='_blank'>phpMyAdmin</a></li>";
echo "<li><a href='database_setup.sql' target='_blank'>ไฟล์ SQL สำหรับ MySQL</a></li>";
echo "<li><a href='test_extensions.php' target='_blank'>ทดสอบ Extensions</a></li>";
echo "</ul>";

echo "<h3>คำสั่ง SQL สำหรับสร้างฐานข้อมูล:</h3>";
echo "<pre>";
echo "CREATE DATABASE zapstock_db;";
echo "</pre>";
?>
