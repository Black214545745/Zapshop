<?php
include 'config.php';

echo "<h2>🔍 ตรวจสอบโครงสร้างตาราง</h2>";

try {
    $conn = getConnection();
    
    // ตรวจสอบตาราง orders
    echo "<h3>ตาราง orders:</h3>";
    $result = pg_query($conn, "SELECT column_name, data_type, is_nullable 
                               FROM information_schema.columns 
                               WHERE table_name = 'orders' 
                               ORDER BY ordinal_position");
    
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
    
    // ตรวจสอบตาราง order_details
    echo "<h3>ตาราง order_details:</h3>";
    $result = pg_query($conn, "SELECT column_name, data_type, is_nullable 
                               FROM information_schema.columns 
                               WHERE table_name = 'order_details' 
                               ORDER BY ordinal_position");
    
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
    
    // ตรวจสอบตาราง user_profiles
    echo "<h3>ตาราง user_profiles:</h3>";
    $result = pg_query($conn, "SELECT column_name, data_type, is_nullable 
                               FROM information_schema.columns 
                               WHERE table_name = 'user_profiles' 
                               ORDER BY ordinal_position");
    
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
?>
