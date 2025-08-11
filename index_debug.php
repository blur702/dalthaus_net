<?php
// Debug version of index.php for MAMP
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug Info</h2>";
echo "<pre>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "GET params: " . print_r($_GET, true) . "\n";
echo "</pre>";

try {
    require_once 'includes/config.php';
    echo "<p>✓ Config loaded</p>";
    
    require_once 'includes/database.php';
    echo "<p>✓ Database module loaded</p>";
    
    require_once 'includes/router.php';
    echo "<p>✓ Router loaded</p>";
    
    require_once 'includes/auth.php';
    echo "<p>✓ Auth loaded</p>";
    
    require_once 'includes/functions.php';
    echo "<p>✓ Functions loaded</p>";
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✓ Session started</p>";
    
    echo "<hr>";
    echo "<p>If all modules loaded, the issue is in routing. Check the routing logic.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}