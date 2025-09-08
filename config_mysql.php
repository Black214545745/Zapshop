<?php
// กำหนดค่า base_url (ถ้ายังไม่มี)
// สำหรับ Localhost
$base_url = "http://localhost/zap_shop"; 

// สำหรับการ Deploy บน Render.com: ดึงค่าจาก Environment Variable
// Render จะใส่ค่า DATABASE_URL ให้คุณอัตโนมัติเมื่อคุณสร้าง Web Service
if (getenv('DATABASE_URL')) {
    $database_url = getenv('DATABASE_URL');
    $url_parts = parse_url($database_url);

    $host = $url_parts['host'];
    $port = $url_parts['port'] ?? 3306;
    $user = $url_parts['user'];
    $password = $url_parts['pass'];
    $dbname = ltrim($url_parts['path'], '/'); // ลบ / ข้างหน้าชื่อฐานข้อมูล

    // สร้าง Connection สำหรับ MySQL
    $conn = new mysqli($host, $user, $password, $dbname, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // ตั้งค่า timezone
    $conn->query("SET timezone = '+07:00'");
    
} else {
    // สำหรับ Localhost - ใช้ MySQL บน Localhost
    $host = 'localhost';
    $port = '3306';
    $dbname = 'zapstock_db';
    $user = 'root';
    $password = '';

    // สร้าง Connection สำหรับ MySQL
    $conn = new mysqli($host, $user, $password, $dbname, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // ตั้งค่า timezone
    $conn->query("SET timezone = '+07:00'");
}

// ฟังก์ชันสำหรับตรวจสอบประเภทฐานข้อมูล
function isPostgreSQL() {
    return false; // ใช้ MySQL
}

// ฟังก์ชันสำหรับ query ที่รองรับ MySQL
function executeQuery($sql, $params = []) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // สมมติว่าทุก parameter เป็น string
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}

// ฟังก์ชันสำหรับดึงข้อมูล
function fetchData($result) {
    return $result->fetch_assoc();
}

// ฟังก์ชันสำหรับนับจำนวนแถว
function numRows($result) {
    return $result->num_rows;
}

// ฟังก์ชันสำหรับปิดการเชื่อมต่อ
function closeConnection() {
    global $conn;
    $conn->close();
}
?>
