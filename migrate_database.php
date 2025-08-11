<?php
/**
 * Database Migration Script
 * Migrates from current structure to match original specification
 * 
 * WARNING: This will restructure your database. Backup first!
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "Starting database migration...\n\n";

$pdo = Database::getInstance();

// Ensure we're using the correct database
$pdo->exec("USE " . DB_NAME);

// Step 1: Create new tables with UUID support
echo "Step 1: Creating new tables with proper structure...\n";

try {
    // Articles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS articles (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            title VARCHAR(255) NOT NULL,
            alias VARCHAR(255) UNIQUE NOT NULL,
            content LONGTEXT,
            excerpt TEXT,
            featured_image VARCHAR(255),
            meta_keywords TEXT,
            meta_description TEXT,
            sort_order INT DEFAULT 0,
            status ENUM('draft', 'published', 'deleted') DEFAULT 'draft',
            published_date DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            INDEX idx_status_sort (status, sort_order),
            INDEX idx_alias (alias),
            INDEX idx_published_date (published_date),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✓ Articles table created\n";

    // Photobooks table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS photobooks (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            title VARCHAR(255) NOT NULL,
            alias VARCHAR(255) UNIQUE NOT NULL,
            body LONGTEXT,
            summary TEXT,
            featured_image VARCHAR(255),
            teaser_image VARCHAR(255),
            meta_keywords TEXT,
            meta_description TEXT,
            sort_order INT DEFAULT 0,
            status ENUM('draft', 'published', 'deleted') DEFAULT 'draft',
            published_date DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            INDEX idx_status_sort (status, sort_order),
            INDEX idx_alias (alias),
            INDEX idx_published_date (published_date),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✓ Photobooks table created\n";

    // Content versions table (updated)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_versions_new (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            content_type ENUM('article', 'photobook') NOT NULL,
            content_id CHAR(36) NOT NULL,
            version_number INT NOT NULL,
            title VARCHAR(255),
            body LONGTEXT,
            summary TEXT,
            excerpt TEXT,
            meta_keywords TEXT,
            meta_description TEXT,
            is_autosave BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_content_lookup (content_type, content_id),
            INDEX idx_version_number (version_number),
            INDEX idx_created_at (created_at)
        )
    ");
    echo "✓ Content versions table created\n";

    // Content attachments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_attachments (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            content_type ENUM('article', 'photobook') NOT NULL,
            content_id CHAR(36) NOT NULL,
            document_name VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_size INT,
            mime_type VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_content_lookup (content_type, content_id),
            INDEX idx_filename (filename)
        )
    ");
    echo "✓ Content attachments table created\n";

    // Site settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_setting_key (setting_key)
        )
    ");
    echo "✓ Site settings table created\n";

    // Content import temporary files
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS content_import_temp (
            id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_size INT,
            mime_type VARCHAR(100),
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_cleanup (uploaded_at)
        )
    ");
    echo "✓ Content import temp table created\n";

    // Menu items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            menu_location ENUM('top', 'bottom') NOT NULL,
            title VARCHAR(255) NOT NULL,
            url VARCHAR(255) NOT NULL,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_location_sort (menu_location, sort_order)
        )
    ");
    echo "✓ Menu items table created\n";

} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Migrate existing content
echo "\nStep 2: Migrating existing content...\n";

try {
    // Check if content table exists
    $result = $pdo->query("SHOW TABLES LIKE 'content'");
    if ($result->rowCount() > 0) {
        // Migrate articles
        $articles = $pdo->query("
            SELECT * FROM content 
            WHERE type = 'article' 
            AND deleted_at IS NULL
        ")->fetchAll();
        
        $stmt = $pdo->prepare("
            INSERT INTO articles (title, alias, content, excerpt, featured_image, 
                                 sort_order, status, published_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($articles as $article) {
            $stmt->execute([
                $article['title'],
                $article['slug'],
                $article['body'],
                substr(strip_tags($article['body']), 0, 500),
                null, // featured_image
                $article['sort_order'],
                $article['status'],
                $article['published_at'] ?? $article['created_at'],
                $article['created_at'],
                $article['updated_at']
            ]);
        }
        echo "✓ Migrated " . count($articles) . " articles\n";
        
        // Migrate photobooks
        $photobooks = $pdo->query("
            SELECT * FROM content 
            WHERE type = 'photobook' 
            AND deleted_at IS NULL
        ")->fetchAll();
        
        $stmt = $pdo->prepare("
            INSERT INTO photobooks (title, alias, body, summary, featured_image, teaser_image,
                                   sort_order, status, published_date, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($photobooks as $book) {
            $stmt->execute([
                $book['title'],
                $book['slug'],
                $book['body'],
                substr(strip_tags($book['body']), 0, 300),
                null, // featured_image
                null, // teaser_image
                $book['sort_order'],
                $book['status'],
                $book['published_at'] ?? $book['created_at'],
                $book['created_at'],
                $book['updated_at']
            ]);
        }
        echo "✓ Migrated " . count($photobooks) . " photobooks\n";
        
        // Migrate attachments if table exists
        $result = $pdo->query("SHOW TABLES LIKE 'attachments'");
        if ($result->rowCount() > 0) {
            echo "✓ Attachments table found, migration needed\n";
        }
    } else {
        echo "⚠ No existing content table found, skipping migration\n";
    }
} catch (PDOException $e) {
    echo "Warning during migration: " . $e->getMessage() . "\n";
}

// Step 3: Insert default settings
echo "\nStep 3: Setting up default site settings...\n";

try {
    $settings = [
        ['site_title', 'Dalthaus.net'],
        ['site_motto', 'Photography & Writing'],
        ['header_height', '200'],
        ['header_image', ''],
        ['header_overlay_color', 'rgba(0,0,0,0.3)'],
        ['copyright_notice', '© ' . date('Y') . ' Dalthaus.net. All rights reserved.'],
        ['homepage_layout', '66-33'],
        ['admin_email', 'admin@dalthaus.net']
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO site_settings (id, setting_key, setting_value) 
        VALUES (UUID(), ?, ?)
    ");
    
    foreach ($settings as $setting) {
        $stmt->execute([$setting[0], $setting[1]]);
    }
    echo "✓ Default settings added\n";
    
    // Add default menu items
    $menuItems = [
        ['top', 'Home', '/', 1],
        ['top', 'Articles', '/articles', 2],
        ['top', 'Photobooks', '/photobooks', 3],
        ['bottom', 'Privacy', '/privacy', 1],
        ['bottom', 'Contact', '/contact', 2]
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO menu_items (menu_location, title, url, sort_order) 
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($menuItems as $item) {
        $stmt->execute($item);
    }
    echo "✓ Default menu items added\n";
    
} catch (PDOException $e) {
    echo "Warning: " . $e->getMessage() . "\n";
}

// Step 4: Update users table for roles
echo "\nStep 4: Updating user management structure...\n";

try {
    // Add is_active column if it doesn't exist
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE AFTER role
    ");
    echo "✓ Users table updated\n";
} catch (PDOException $e) {
    echo "Note: " . $e->getMessage() . "\n";
}

echo "\n=================================\n";
echo "Migration completed successfully!\n";
echo "=================================\n\n";
echo "Next steps:\n";
echo "1. Update your application code to use the new table structure\n";
echo "2. Test all functionality thoroughly\n";
echo "3. Once verified, you can remove the old 'content' table\n";
echo "\nIMPORTANT: The application needs to be updated to work with the new structure.\n";