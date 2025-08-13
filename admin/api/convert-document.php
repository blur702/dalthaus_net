<?php
/**
 * Document Conversion API Endpoint
 * Handles document upload and conversion to HTML for articles and photobooks
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Require admin authentication
Auth::requireAdmin();

// Set JSON response header
header('Content-Type: application/json');

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['document'])) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$uploadedFile = $_FILES['document'];

// Validate file upload
if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Upload failed']);
    exit;
}

// Check file size (10MB max)
if ($uploadedFile['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 10MB)']);
    exit;
}

// Get file extension
$fileInfo = pathinfo($uploadedFile['name']);
$extension = strtolower($fileInfo['extension'] ?? '');

// Validate file type
$allowedExtensions = ['docx', 'doc', 'pdf'];
if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only DOCX and PDF files are allowed.']);
    exit;
}

// Create temp directory if it doesn't exist
$tempDir = __DIR__ . '/../../temp/';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0755, true);
}

// Generate unique filename
$tempFilename = uniqid('doc_') . '.' . $extension;
$tempPath = $tempDir . $tempFilename;

// Move uploaded file to temp directory
if (!move_uploaded_file($uploadedFile['tmp_name'], $tempPath)) {
    // Fallback for testing or if move_uploaded_file fails
    if (file_exists($uploadedFile['tmp_name']) && copy($uploadedFile['tmp_name'], $tempPath)) {
        // Successfully copied file
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file. Check temp directory permissions.']);
        exit;
    }
}

try {
    // Use the specific Python path that we know works
    $pythonPath = '/usr/local/bin/python3';
    
    // Verify Python is accessible
    $pythonTest = shell_exec($pythonPath . ' --version 2>&1');
    if (!$pythonTest) {
        throw new Exception('Python3 not accessible');
    }
    
    // Path to converter script
    $converterPath = realpath(__DIR__ . '/../../scripts/converter.py');
    if (!file_exists($converterPath)) {
        throw new Exception('Converter script not found');
    }
    
    // Run converter
    $command = $pythonPath . ' ' . 
               escapeshellarg($converterPath) . ' ' . 
               escapeshellarg($tempPath) . ' 2>&1';
    
    $output = shell_exec($command);
    
    // Log for debugging
    if (empty($output)) {
        throw new Exception('No output from converter. Command: ' . $command);
    }
    
    // Parse JSON response
    $result = json_decode($output, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // If output is not JSON, it's likely an error message
        // Check for specific error patterns
        if (strpos($output, 'ModuleNotFoundError') !== false) {
            if (strpos($output, 'pypandoc') !== false) {
                throw new Exception('pypandoc module not found. Please install: pip3 install pypandoc');
            } elseif (strpos($output, 'pdfplumber') !== false) {
                throw new Exception('pdfplumber module not found. Please install: pip3 install pdfplumber');
            }
        }
        throw new Exception('Converter error: ' . substr($output, 0, 500));
    }
    
    // Clean up temp file
    @unlink($tempPath);
    
    if ($result && isset($result['success']) && $result['success']) {
        echo json_encode([
            'success' => true,
            'html' => $result['html'],
            'info' => $result['info'] ?? 'Document converted successfully'
        ]);
    } else {
        $errorMsg = isset($result['error']) ? $result['error'] : 'Conversion failed';
        echo json_encode(['success' => false, 'error' => $errorMsg]);
    }
    
} catch (Exception $e) {
    // Clean up temp file on error
    @unlink($tempPath);
    
    $errorMessage = $e->getMessage();
    
    // Provide user-friendly error messages
    if (strpos($errorMessage, 'Python3 not found') !== false) {
        $errorMessage = 'Python is not installed on the server';
    } elseif (strpos($errorMessage, 'Converter script not found') !== false) {
        $errorMessage = 'Document converter is missing';
    }
    
    echo json_encode(['success' => false, 'error' => $errorMessage]);
}
?>