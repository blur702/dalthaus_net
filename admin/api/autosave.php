<?php
declare(strict_types=1);
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

session_start();

// Check authentication
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Get data
$contentId = (int)($_POST['content_id'] ?? 0);
$title = $_POST['title'] ?? '';
$body = $_POST['body'] ?? '';

if (!$contentId) {
    echo json_encode(['success' => false, 'error' => 'Invalid content ID']);
    exit;
}

try {
    $pdo = Database::getInstance();
    
    // Check if content exists and user has access
    $stmt = $pdo->prepare("SELECT id FROM content WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$contentId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Content not found']);
        exit;
    }
    
    // Get current version number
    $stmt = $pdo->prepare("SELECT MAX(version_number) as max_version FROM content_versions WHERE content_id = ?");
    $stmt->execute([$contentId]);
    $result = $stmt->fetch();
    $versionNumber = ($result['max_version'] ?? 0) + 1;
    
    // Save autosave version
    $stmt = $pdo->prepare("
        INSERT INTO content_versions (content_id, version_number, title, body, is_autosave) 
        VALUES (?, ?, ?, ?, TRUE)
    ");
    
    $stmt->execute([$contentId, $versionNumber, $title, $body]);
    
    echo json_encode([
        'success' => true,
        'version_number' => $versionNumber,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    logMessage('Autosave error: ' . $e->getMessage(), 'error');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}