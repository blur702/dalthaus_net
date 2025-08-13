<?php
/**
 * Core Utility Functions
 * 
 * Collection of helper functions for common CMS operations including:
 * - CSRF protection
 * - Input sanitization and validation
 * - Caching operations
 * - Logging and error handling
 * - File management
 * - Content processing
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
declare(strict_types=1);

/**
 * Generate or retrieve CSRF token for current session
 * 
 * Creates a new token if none exists, otherwise returns existing token.
 * Token is stored in session for validation on form submission.
 * 
 * @return string 64-character hexadecimal CSRF token
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate submitted CSRF token against session token
 * 
 * Uses timing-safe comparison to prevent timing attacks.
 * Returns false if no session token exists.
 * 
 * @param string $token Token submitted with form/AJAX request
 * @return bool True if tokens match, false otherwise
 */
function validateCSRFToken(string $token): bool {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize user input for safe HTML output
 * 
 * Trims whitespace and escapes HTML special characters.
 * Prevents XSS attacks when displaying user-submitted content.
 * 
 * @param string $input Raw user input
 * @return string Sanitized string safe for HTML output
 */
function sanitizeInput(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Create URL-friendly slug from title
 * 
 * Converts to lowercase, replaces non-alphanumeric with hyphens,
 * removes consecutive hyphens, and trims edge hyphens.
 * Falls back to 'untitled' if result is empty.
 * 
 * @param string $title Original title text
 * @return string URL-safe slug
 */
function createSlug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'untitled';
}

/**
 * Retrieve value from file cache
 * 
 * Returns null if cache disabled, file missing, or expired.
 * Uses serialized data storage with expiration timestamps.
 * 
 * @param string $key Cache key identifier
 * @return mixed Cached value or null if not found/expired
 */
function cacheGet(string $key): mixed {
    if (!CACHE_ENABLED) return null;
    
    $file = CACHE_PATH . '/' . md5($key) . '.cache';
    if (!file_exists($file)) return null;
    
    $data = unserialize(file_get_contents($file));
    if ($data['expires'] < time()) {
        unlink($file);
        return null;
    }
    return $data['value'];
}

/**
 * Store value in file cache with expiration
 * 
 * Creates cache directory if needed.
 * Stores serialized data with expiration timestamp.
 * 
 * @param string $key Cache key identifier
 * @param mixed $value Value to cache (must be serializable)
 * @param int $ttl Time-to-live in seconds (default: CACHE_TTL)
 * @return bool True if cached successfully, false otherwise
 */
function cacheSet(string $key, mixed $value, int $ttl = CACHE_TTL): bool {
    if (!CACHE_ENABLED) return false;
    if (!is_dir(CACHE_PATH)) mkdir(CACHE_PATH, 0755, true);
    
    $file = CACHE_PATH . '/' . md5($key) . '.cache';
    $data = ['value' => $value, 'expires' => time() + $ttl];
    return file_put_contents($file, serialize($data)) !== false;
}

/**
 * Clear all cached files
 * 
 * Removes all .cache files from cache directory.
 * Used when content changes require cache invalidation.
 * 
 * @return void
 */
function cacheClear(): void {
    if (is_dir(CACHE_PATH)) {
        array_map('unlink', glob(CACHE_PATH . '/*.cache'));
    }
}

/**
 * Write message to application log with automatic rotation
 * 
 * Appends timestamped entry to log file.
 * Automatically rotates log when line limit reached.
 * Creates log directory if it doesn't exist.
 * 
 * @param string $message Log message text
 * @param string $level Log level (info, warning, error, debug)
 * @return void
 */
function logMessage(string $message, string $level = 'info'): void {
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    $logFile = LOG_PATH . '/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    rotateLog();
}

/**
 * Rotate log file when size limit exceeded
 * 
 * Keeps only the last LOG_MAX_LINES lines.
 * Prevents log files from growing too large.
 * 
 * @return void
 */
function rotateLog(): void {
    $logFile = LOG_PATH . '/app.log';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        if (count($lines) > LOG_MAX_LINES) {
            $kept = array_slice($lines, -LOG_MAX_LINES);
            file_put_contents($logFile, implode(PHP_EOL, $kept) . PHP_EOL);
        }
    }
}

/**
 * Remove temporary files older than 24 hours
 * 
 * Cleans up document import and other temporary files.
 * Should be run via cron job for automatic maintenance.
 * 
 * @return void
 */
function cleanTempFiles(): void {
    if (!is_dir(TEMP_PATH)) return;
    
    $files = glob(TEMP_PATH . '/*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file) > 86400)) {
            unlink($file);
        }
    }
}

/**
 * Process content HTML to handle missing images
 * 
 * Replaces missing image tags with placeholder divs.
 * Preserves image classes for styling (full, left, right, center).
 * Used to prevent broken image icons in content display.
 * 
 * @param string $content HTML content with img tags
 * @return string Processed HTML with placeholders for missing images
 */
function processContentImages(string $content): string {
    // Pattern to match img tags
    $pattern = '/<img\s+([^>]*?)src=["\']([^"\']+)["\']([^>]*?)>/i';
    
    return preg_replace_callback($pattern, function($matches) {
        $fullMatch = $matches[0];
        $beforeSrc = $matches[1];
        $src = $matches[2];
        $afterSrc = $matches[3];
        
        // Check if image exists (for local images)
        if (strpos($src, 'http') !== 0 && strpos($src, '//') !== 0) {
            $imagePath = $_SERVER['DOCUMENT_ROOT'] . $src;
            if (!file_exists($imagePath)) {
                // Extract classes from the img tag
                $classes = '';
                if (preg_match('/class=["\']([^"\']*)["\']/', $fullMatch, $classMatch)) {
                    $classes = $classMatch[1];
                }
                
                // Determine size based on classes
                $sizeClass = 'medium';
                if (strpos($classes, 'img-full') !== false) {
                    $sizeClass = 'large img-full';
                } elseif (strpos($classes, 'img-left') !== false) {
                    $sizeClass = 'medium img-left';
                } elseif (strpos($classes, 'img-right') !== false) {
                    $sizeClass = 'medium img-right';
                } elseif (strpos($classes, 'img-center') !== false) {
                    $sizeClass = 'medium img-center';
                }
                
                // Return placeholder div
                return '<div class="image-placeholder ' . $sizeClass . '" aria-label="Image placeholder"></div>';
            }
        }
        
        return $fullMatch;
    }, $content);
}

/**
 * Display error page and terminate execution
 * 
 * Redirects to error.php with specified HTTP status code.
 * 
 * @param int $code HTTP error code (default: 404)
 * @param string $message Optional error message (unused in current implementation)
 * @return void
 */
function showError(int $code = 404, string $message = ''): void {
    // Set proper HTTP status code
    http_response_code($code);
    
    // Include error page if it exists, otherwise show simple error
    $errorPage = __DIR__ . '/../public/error.php';
    if (file_exists($errorPage)) {
        $_GET['code'] = $code;
        $_GET['message'] = $message;
        include $errorPage;
    } else {
        // Simple error page
        echo "<!DOCTYPE html><html><head><title>Error $code</title></head>";
        echo "<body><h1>Error $code</h1>";
        echo $message ? "<p>$message</p>" : "<p>The requested page could not be found.</p>";
        echo "<p><a href='/'>Return to homepage</a></p></body></html>";
    }
    exit;
}

/**
 * Global exception handler
 * 
 * Logs exception details and displays appropriate error page.
 * Shows detailed error in development, generic error in production.
 * 
 * @param Throwable $e Exception or error to handle
 * @return void
 */
function handleException(Throwable $e): void {
    // Log the error
    logMessage('Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'error');
    
    // Show user-friendly error page
    if (ENVIRONMENT === 'production') {
        showError(500);
    } else {
        // In development, show detailed error
        echo '<pre>';
        echo 'Error: ' . htmlspecialchars($e->getMessage()) . "\n";
        echo 'File: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . "\n";
        echo 'Trace: ' . "\n" . htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
        exit;
    }
}