<?php
include 'config.php';

echo "<h2>🔍 ตรวจสอบโครงสร้างตาราง orders</h2>";

try {
    $conn = getConnection();
    
    // ตรวจสอบตาราง orders
    $result = pg_query($conn, "SELECT column_name, data_type 
                               FROM information_schema.columns 
                               WHERE table_name = 'orders' 
                               ORDER BY ordinal_position");
    
    if ($result) {
        echo "<h3>คอลัมน์ในตาราง orders:</h3>";
        echo "<ul>";
        while ($row = pg_fetch_assoc($result)) {
            echo "<li><strong>{$row['column_name']}</strong> - {$row['data_type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>ไม่สามารถดึงข้อมูลได้: " . pg_last_error($conn) . "</p>";
    }
    
    pg_close($conn);
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
