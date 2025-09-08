<?php
session_start();

echo "Testing user-login.php...<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";

// ทดสอบ include config.php
echo "<h3>Testing config.php:</h3>";
if (file_exists('config.php')) {
    echo "✅ config.php exists<br>";
    
    try {
        include 'config.php';
        echo "✅ config.php included successfully<br>";
        
        // ทดสอบฟังก์ชัน getUserByUsername
        if (function_exists('getUserByUsername')) {
            echo "✅ getUserByUsername function exists<br>";
        } else {
            echo "❌ getUserByUsername function not found<br>";
        }
        
        // ทดสอบฟังก์ชัน logActivity
        if (function_exists('logActivity')) {
            echo "✅ logActivity function exists<br>";
        } else {
            echo "❌ logActivity function not found<br>";
        }
        
        // ทดสอบฟังก์ชัน createNotification
        if (function_exists('createNotification')) {
            echo "✅ createNotification function exists<br>";
        } else {
            echo "❌ createNotification function not found<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error including config.php: " . $e->getMessage() . "<br>";
    } catch (Error $e) {
        echo "❌ Fatal Error including config.php: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ config.php not found<br>";
}

// ทดสอบ Session
echo "<h3>Session Test:</h3>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";

// ทดสอบ Extensions
echo "<h3>Extensions Test:</h3>";
$extensions = ['pgsql', 'json', 'session'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✅" : "❌") . "<br>";
}

echo "<hr>";
echo "<p><a href='test_simple.php'>Test Simple</a></p>";
echo "<p><a href='debug_simple.php'>Debug Simple</a></p>";
echo "<p><a href='user-login.php'>Original Login</a></p>";
?>
