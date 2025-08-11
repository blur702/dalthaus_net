<?php
/**
 * Configuration File
 * 
 * Central configuration for the Dalthaus.net CMS.
 * Contains all system constants including database settings, paths, security parameters,
 * and environment-specific configurations.
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
declare(strict_types=1);

// ============================================================================
// DATABASE CONFIGURATION
// ============================================================================

/**
 * Database host address
 * Use 127.0.0.1 instead of localhost for better MAMP/XAMPP compatibility
 * @var string
 */
define('DB_HOST', '127.0.0.1');

/**
 * Database name for the CMS
 * @var string
 */
define('DB_NAME', 'dalthaus_cms');

/**
 * Database username
 * Default 'root' for local development environments
 * @var string
 */
define('DB_USER', 'root');

/**
 * Database password
 * Leave empty for default MySQL/MAMP installations
 * @var string
 */
define('DB_PASS', '');

// ============================================================================
// ENVIRONMENT CONFIGURATION
// ============================================================================

/**
 * Current environment mode
 * Options: 'development', 'staging', 'production'
 * Affects error reporting, caching, and debug output
 * @var string
 */
define('ENV', 'development');

// ============================================================================
// PYTHON INTEGRATION
// ============================================================================

/**
 * Path to Python 3 executable
 * Automatically detects system Python or falls back to PATH
 * Required for document conversion (Word/PDF to HTML)
 * @var string
 */
define('PYTHON_PATH', file_exists('/usr/bin/python3') ? '/usr/bin/python3' : 'python3');

/**
 * Path to the document converter Python script
 * Converts Word and PDF documents to TinyMCE-compatible HTML
 * @var string
 */
define('CONVERTER_SCRIPT', dirname(__DIR__) . '/scripts/converter.py');

// ============================================================================
// TESTING CONFIGURATION
// ============================================================================

/**
 * Test mode flag
 * When true, uses TEST_DATABASE instead of DB_NAME
 * @var bool
 */
define('TEST_MODE', false);

/**
 * Test database name
 * Separate database for running unit and integration tests
 * @var string
 */
define('TEST_DATABASE', 'dalthaus_test');

// ============================================================================
// SECURITY CONFIGURATION
// ============================================================================

/**
 * Session lifetime in seconds (1 hour default)
 * User sessions expire after this period of inactivity
 * @var int
 */
define('SESSION_LIFETIME', 3600);

/**
 * CSRF token field name
 * Used in forms and AJAX requests for CSRF protection
 * @var string
 */
define('CSRF_TOKEN_NAME', 'csrf_token');

/**
 * Maximum failed login attempts before lockout
 * @var int
 */
define('MAX_LOGIN_ATTEMPTS', 5);

/**
 * Login lockout duration in seconds (15 minutes)
 * Time user must wait after MAX_LOGIN_ATTEMPTS failures
 * @var int
 */
define('LOGIN_LOCKOUT_TIME', 900);

/**
 * Maximum file upload size in bytes (10MB)
 * @var int
 */
define('UPLOAD_MAX_SIZE', 10485760);

/**
 * Allowed file extensions for uploads
 * @var array<string>
 */
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// ============================================================================
// DEFAULT ADMIN CREDENTIALS
// ============================================================================

/**
 * Default admin username
 * Used for initial setup - should be changed after first login
 * @var string
 */
define('DEFAULT_ADMIN_USER', 'admin');

/**
 * Default admin password
 * IMPORTANT: Change immediately after initial setup
 * @var string
 */
define('DEFAULT_ADMIN_PASS', '130Bpm');

// ============================================================================
// FILE SYSTEM PATHS
// ============================================================================

/**
 * Absolute path to application root directory
 * @var string
 */
define('ROOT_PATH', dirname(__DIR__));

/**
 * Path to user upload directory
 * Stores images, documents, and other user files
 * @var string
 */
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

/**
 * Path to cache directory
 * Stores rendered page cache for performance
 * @var string
 */
define('CACHE_PATH', ROOT_PATH . '/cache');

/**
 * Path to log directory
 * Contains application and error logs
 * @var string
 */
define('LOG_PATH', ROOT_PATH . '/logs');

/**
 * Path to temporary files directory
 * Used for document imports, cleaned every 24 hours
 * @var string
 */
define('TEMP_PATH', ROOT_PATH . '/temp');

// ============================================================================
// LOGGING CONFIGURATION
// ============================================================================

/**
 * Maximum lines per log file
 * Log rotation occurs when this limit is reached
 * @var int
 */
define('LOG_MAX_LINES', 5000);

/**
 * Logging level based on environment
 * 'debug' in development, 'error' in production
 * @var string
 */
define('LOG_LEVEL', ENV === 'development' ? 'debug' : 'error');

// ============================================================================
// CACHE CONFIGURATION
// ============================================================================

/**
 * Cache enabled flag
 * Disabled in development for easier debugging
 * @var bool
 */
define('CACHE_ENABLED', ENV !== 'development');

/**
 * Cache time-to-live in seconds (1 hour)
 * Cached pages expire after this duration
 * @var int
 */
define('CACHE_TTL', 3600);