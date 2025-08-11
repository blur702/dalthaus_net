<?php
declare(strict_types=1);
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

session_start();

// Check authentication
if (!Auth::isLoggedIn() || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!validateCSRFToken($input['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$location = $input['location'] ?? '';
$order = $input['order'] ?? [];

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'No order data provided']);
    exit;
}

try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();
    
    // Update sort order for each item
    $stmt = $pdo->prepare("UPDATE menus SET sort_order = ? WHERE id = ?");
    
    foreach ($order as $item) {
        $stmt->execute([$item['order'], $item['id']]);
    }
    
    $pdo->commit();
    
    // Clear cache
    cacheClear();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    logMessage('Sort error: ' . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}