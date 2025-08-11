<?php
// MAMP Pro debugging script
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>MAMP Pro Environment Test</h2>";

echo "<h3>PHP Version:</h3>";
echo phpversion() . "<br><br>";

echo "<h3>Current Directory:</h3>";
echo __DIR__ . "<br><br>";

echo "<h3>Checking Required Files:</h3>";
$files = [
    'includes/config.php',
    'includes/database.php',
    'includes/router.php',
    'includes/auth.php',
    'includes/functions.php',
    '.htaccess'
];

foreach ($files as $file) {
    echo $file . ": ";
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ Found<br>";
    } else {
        echo "✗ Missing<br>";
    }
}

echo "<h3>Checking Directories:</h3>";
$dirs = ['uploads', 'cache', 'logs', 'temp'];
foreach ($dirs as $dir) {
    echo $dir . ": ";
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "✓ Exists";
        if (is_writable(__DIR__ . '/' . $dir)) {
            echo " (writable)";
        } else {
            echo " (NOT writable - needs chmod 755)";
        }
    } else {
        echo "✗ Missing";
    }
    echo "<br>";
}

echo "<h3>PDO MySQL Extension:</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "✓ Loaded<br>";
} else {
    echo "✗ Not loaded<br>";
}

echo "<h3>Testing Database Connection:</h3>";
try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    $pdo = Database::getInstance();
    echo "✓ Database connection successful<br>";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><hr>";
echo "<p>If you see errors above, they indicate what needs to be fixed.</p>";