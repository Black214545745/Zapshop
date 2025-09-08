<?php
echo "<h2>PHP Database Extensions Check</h2>";

echo "<h3>Available Extensions:</h3>";
$extensions = get_loaded_extensions();
$db_extensions = [];

foreach ($extensions as $ext) {
    if (strpos($ext, 'pgsql') !== false || 
        strpos($ext, 'mysql') !== false || 
        strpos($ext, 'mysqli') !== false || 
        strpos($ext, 'pdo') !== false) {
        $db_extensions[] = $ext;
    }
}

if (empty($db_extensions)) {
    echo "<p style='color: red;'>No database extensions found!</p>";
} else {
    echo "<ul>";
    foreach ($db_extensions as $ext) {
        echo "<li>$ext</li>";
    }
    echo "</ul>";
}

echo "<h3>Specific Database Extensions:</h3>";
echo "<ul>";
echo "<li>PostgreSQL (pgsql): " . (extension_loaded('pgsql') ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>') . "</li>";
echo "<li>PostgreSQL PDO (pdo_pgsql): " . (extension_loaded('pdo_pgsql') ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>') . "</li>";
echo "<li>MySQL (mysqli): " . (extension_loaded('mysqli') ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>') . "</li>";
echo "<li>MySQL PDO (pdo_mysql): " . (extension_loaded('pdo_mysql') ? '<span style="color: green;">✓ Available</span>' : '<span style="color: red;">✗ Not Available</span>') . "</li>";
echo "</ul>";

echo "<h3>PHP Version:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<h3>PHP Info:</h3>";
echo "<p><a href='phpinfo.php' target='_blank'>View phpinfo()</a></p>";
?>
