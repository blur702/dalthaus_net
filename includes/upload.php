<?php
declare(strict_types=1);

class FileUploader {
    private array $allowedExtensions;
    private int $maxSize;
    private string $uploadPath;
    
    public function __construct() {
        $this->allowedExtensions = ALLOWED_EXTENSIONS;
        $this->maxSize = UPLOAD_MAX_SIZE;
        $this->uploadPath = UPLOAD_PATH;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    public function upload(array $file): array {
        // Validate file upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadError($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            return ['success' => false, 'error' => 'File too large. Maximum size: ' . ($this->maxSize / 1048576) . ' MB'];
        }
        
        // Get file extension
        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Check extension
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['success' => false, 'error' => 'File type not allowed'];
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!$this->isValidMimeType($mimeType, $extension)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
        $destination = $this->uploadPath . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }
        
        // Set proper permissions
        chmod($destination, 0644);
        
        return [
            'success' => true,
            'filename' => $filename,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }
    
    private function isValidMimeType(string $mimeType, string $extension): bool {
        $validTypes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        ];
        
        return isset($validTypes[$extension]) && in_array($mimeType, $validTypes[$extension]);
    }
    
    private function getUploadError(int $error): string {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File too large';
            case UPLOAD_ERR_PARTIAL:
                return 'File upload incomplete';
            case UPLOAD_ERR_NO_FILE:
                return 'No file uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server error';
            default:
                return 'Unknown upload error';
        }
    }
}