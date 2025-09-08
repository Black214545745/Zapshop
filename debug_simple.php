<?php
// ไฟล์ debug ง่ายๆ
echo "PHP ทำงานได้!<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Time: " . date('Y-m-d H:i:s') . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// ทดสอบ Extensions พื้นฐาน
echo "<h3>Extensions Test:</h3>";
$extensions = ['pgsql', 'json', 'session', 'mbstring'];
foreach ($extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✅" : "❌") . "<br>";
}

// ทดสอบ Session
echo "<h3>Session Test:</h3>";
try {
    session_start();
    echo "Session: ✅ Started<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "Session: ❌ " . $e->getMessage() . "<br>";
}

// ทดสอบ JSON
echo "<h3>JSON Test:</h3>";
try {
    $test = json_encode(['test' => 'data']);
    echo "JSON: ✅ " . $test . "<br>";
} catch (Exception $e) {
    echo "JSON: ❌ " . $e->getMessage() . "<br>";
}

// ทดสอบ PostgreSQL
echo "<h3>PostgreSQL Test:</h3>";
if (extension_loaded('pgsql')) {
    echo "PostgreSQL Extension: ✅ Loaded<br>";
} else {
    echo "PostgreSQL Extension: ❌ Not Loaded<br>";
}

echo "<hr>";
echo "<p><a href='test_simple.php'>Test Simple</a></p>";
echo "<p><a href='debug_cart.php'>Debug Cart</a></p>";
echo "<p><a href='cart.php'>Cart</a></p>";
?>
