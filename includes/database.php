<?php
/**
 * Database Connection and Management Class
 * 
 * Provides a singleton PDO database connection with automatic setup capabilities.
 * Handles database creation, table initialization, and connection management.
 * Supports both production and test databases based on TEST_MODE configuration.
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/**
 * Database singleton class for PDO connection management
 */
class Database {
    /**
     * Singleton PDO instance
     * @var PDO|null
     */
    private static ?PDO $instance = null;
    
    /**
     * Get or create the singleton PDO database connection
     * 
     * Creates a new PDO connection if none exists, using configuration from config.php.
     * Automatically selects between production and test databases based on TEST_MODE.
     * Sets PDO attributes for error handling, fetch mode, and prepared statement emulation.
     * 
     * @return PDO The database connection instance
     * @throws PDOException If connection fails (except for missing database during setup)
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST);
                self::$instance = new PDO($dsn, DB_USER, DB_PASS);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                
                // Select database if exists
                $dbName = TEST_MODE ? TEST_DATABASE : DB_NAME;
                self::$instance->exec("USE `$dbName`");
            } catch (PDOException $e) {
                // Database might not exist yet, that's ok for setup
                if (strpos($e->getMessage(), 'Unknown database') === false) {
                    throw $e;
                }
            }
        }
        return self::$instance;
    }
    
    /**
     * Initialize database with tables and default data
     * 
     * Performs complete database setup:
     * 1. Creates database if it doesn't exist
     * 2. Creates all required tables with indexes
     * 3. Seeds initial data (default admin user)
     * 
     * Safe to run multiple times - uses IF NOT EXISTS clauses
     * 
     * @return void
     * @throws PDOException If database operations fail
     */
    public static function setup(): void {
        $pdo = self::getInstance();
        $dbName = TEST_MODE ? TEST_DATABASE : DB_NAME;
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
        $pdo->exec("USE `$dbName`");
        
        self::createTables($pdo);
        self::seedData($pdo);
    }
    
    /**
     * Create all database tables with proper structure and indexes
     * 
     * Creates the following tables:
     * - users: User accounts with authentication
     * - content: Unified content storage (articles, photobooks, pages)
     * - content_versions: Version history with autosave support
     * - menus: Menu items for navigation
     * - attachments: File attachments linked to content
     * - settings: Key-value configuration storage
     * - sessions: Custom session handler data
     * 
     * @param PDO $pdo Database connection
     * @return void
     * @throws PDOException If table creation fails
     */
    private static function createTables(PDO $pdo): void {
        // Users table - stores admin and editor accounts
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role ENUM('admin', 'editor') DEFAULT 'editor',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username)
        )");
        
        // Content table - unified storage for articles, photobooks, and pages
        $pdo->exec("CREATE TABLE IF NOT EXISTS content (
            id INT PRIMARY KEY AUTO_INCREMENT,
            type ENUM('article', 'photobook', 'page') NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            author VARCHAR(100) DEFAULT 'Don Althaus',
            body LONGTEXT,
            status ENUM('draft', 'published') DEFAULT 'draft',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            published_at TIMESTAMP NULL,
            INDEX idx_slug (slug),
            INDEX idx_type_status (type, status),
            INDEX idx_sort (sort_order)
        )");
        
        // Content versions - tracks all content changes with autosave support
        $pdo->exec("CREATE TABLE IF NOT EXISTS content_versions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content_id INT NOT NULL,
            version_number INT NOT NULL,
            title VARCHAR(255),
            body LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_autosave BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
            INDEX idx_content_version (content_id, version_number)
        )");
        
        // Menus - navigation items for top and bottom menus
        $pdo->exec("CREATE TABLE IF NOT EXISTS menus (
            id INT PRIMARY KEY AUTO_INCREMENT,
            location ENUM('top', 'bottom') NOT NULL,
            content_id INT,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
            INDEX idx_location_order (location, sort_order)
        )");
        
        // Attachments - file uploads linked to content
        $pdo->exec("CREATE TABLE IF NOT EXISTS attachments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content_id INT,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255),
            mime_type VARCHAR(100),
            size INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
        )");
        
        // Settings - key-value configuration storage
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(50) PRIMARY KEY,
            `value` TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Sessions - custom session handler with fingerprinting
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT,
            data TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_last_activity (last_activity)
        )");
    }
    
    /**
     * Seed database with initial data
     * 
     * Creates default admin user if no users exist.
     * Uses credentials from DEFAULT_ADMIN_USER and DEFAULT_ADMIN_PASS constants.
     * Password is properly hashed using password_hash().
     * 
     * @param PDO $pdo Database connection
     * @return void
     */
    private static function seedData(PDO $pdo): void {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')")
                ->execute([DEFAULT_ADMIN_USER, $hash]);
        }
    }
}

// Command-line interface for database setup
// Usage: php includes/database.php --setup
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    if ($argv[1] === '--setup') {
        try {
            Database::setup();
            echo "Database setup complete\n";
        } catch (Exception $e) {
            echo "Database setup failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}