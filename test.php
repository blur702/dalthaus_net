<?php
echo "<h1>PHP is working!</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current Directory: " . __DIR__ . "</p>";

// Test database connection
echo "<h2>Testing Database Connection:</h2>";
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    echo "<p style='color:green;'>✓ Can connect to MySQL server</p>";
    
    // Try to create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS dalthaus_cms");
    echo "<p style='color:green;'>✓ Database 'dalthaus_cms' exists or was created</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p>Try these credentials in includes/config.php:</p>";
    echo "<pre>";
    echo "define('DB_HOST', '127.0.0.1');\n";
    echo "define('DB_USER', 'root');\n";
    echo "define('DB_PASS', '');  // empty password\n";
    echo "</pre>";
}

echo "<hr>";
echo "<p><a href='/'>Try loading the homepage</a></p>";