<?php
echo "<h2>ทดสอบการเชื่อมต่อ PostgreSQL</h2>";

// ตรวจสอบ PostgreSQL extension
echo "<h3>1. ตรวจสอบ PostgreSQL Extension:</h3>";
if (extension_loaded('pgsql')) {
    echo "<p style='color: green;'>✓ PostgreSQL extension (pgsql) พร้อมใช้งาน</p>";
} else {
    echo "<p style='color: red;'>✗ PostgreSQL extension (pgsql) ไม่พร้อมใช้งาน</p>";
    echo "<p>กรุณาเปิดใช้งานใน php.ini:</p>";
    echo "<ul>";
    echo "<li>เปิดไฟล์: C:\\xampp\\php\\php.ini</li>";
    echo "<li>ค้นหา: ;extension=pgsql</li>";
    echo "<li>ลบเครื่องหมาย ; ออก</li>";
    echo "<li>รีสตาร์ท Apache</li>";
    echo "</ul>";
    exit;
}

// ทดสอบการเชื่อมต่อ
echo "<h3>2. ทดสอบการเชื่อมต่อฐานข้อมูล:</h3>";

try {
    // ใช้ข้อมูลจาก config.php
    $host = 'dpg-d2q1vder433s73dqf0lg-a.oregon-postgres.render.com';
    $port = '5432';
    $dbname = 'zapstock_db';
    $user = 'zapstock_user';
    $password = 'jb3uWpZlFoG3f2d1PI21ZFX0frHSGrDW';

    // สร้าง Connection String
    $conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
    
    echo "<p>กำลังเชื่อมต่อไปยัง: $host:$port</p>";
    
    $conn = pg_connect($conn_string);

    if (!$conn) {
        echo "<p style='color: red;'>✗ การเชื่อมต่อล้มเหลว: " . pg_last_error() . "</p>";
    } else {
        echo "<p style='color: green;'>✓ การเชื่อมต่อสำเร็จ!</p>";
        
        // ทดสอบ query
        echo "<h3>3. ทดสอบ Query:</h3>";
        $result = pg_query($conn, "SELECT version()");
        if ($result) {
            $row = pg_fetch_assoc($result);
            echo "<p>PostgreSQL Version: " . $row['version'] . "</p>";
        }
        
        // ทดสอบตาราง
        echo "<h3>4. ตรวจสอบตาราง:</h3>";
        $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        if ($result) {
            echo "<ul>";
            while ($row = pg_fetch_assoc($result)) {
                echo "<li>" . $row['table_name'] . "</li>";
            }
            echo "</ul>";
        }
        
        pg_close($conn);
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
}

echo "<h3>5. ข้อมูลการเชื่อมต่อ:</h3>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>Port: $port</li>";
echo "<li>Database: $dbname</li>";
echo "<li>User: $user</li>";
echo "</ul>";

echo "<h3>6. ขั้นตอนต่อไป:</h3>";
echo "<p>หากการเชื่อมต่อสำเร็จ ให้รันไฟล์ SQL เพื่อสร้างตาราง:</p>";
echo "<p><a href='database_setup_postgresql.sql' target='_blank'>เปิดไฟล์ database_setup_postgresql.sql</a></p>";
?>
