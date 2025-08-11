<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$filename = $_GET['params'][0] ?? '';

if (!$filename) {
    http_response_code(404);
    echo "File not found";
    exit;
}

// Sanitize filename
$filename = basename($filename);
$filepath = UPLOAD_PATH . '/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo "File not found";
    exit;
}

// Get file info from database
$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT * FROM attachments WHERE filename = ?");
$stmt->execute([$filename]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    echo "File not found";
    exit;
}

// Check if file requires authentication
if ($attachment['content_id']) {
    // Check if content is published
    $stmt = $pdo->prepare("
        SELECT status FROM content 
        WHERE id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$attachment['content_id']]);
    $content = $stmt->fetch();
    
    // If content is not published, require admin auth
    if (!$content || $content['status'] !== 'published') {
        session_start();
        require_once '../includes/auth.php';
        if (!Auth::isLoggedIn()) {
            http_response_code(403);
            echo "Access denied";
            exit;
        }
    }
}

// Serve file
$mimeType = $attachment['mime_type'] ?: 'application/octet-stream';
$size = filesize($filepath);

// Set headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $size);
header('Content-Disposition: inline; filename="' . $attachment['original_name'] . '"');
header('Cache-Control: public, max-age=86400');

// Output file
readfile($filepath);