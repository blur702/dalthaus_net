<?php
/**
 * Migration: Update articles table with page tracking
 * 
 * Adds page tracking columns to the old articles table.
 * This ensures compatibility until the front-end is fully migrated.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/page_tracker.php';

try {
    $pdo = Database::getInstance();
    
    echo "Starting migration: Update articles table page tracking...\n";
    
    // Check if articles table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'articles'")->fetchAll();
    if (empty($tables)) {
        echo "Articles table doesn't exist, skipping.\n";
        exit(0);
    }
    
    // Check if columns already exist in articles table
    $checkCol = $pdo->query("SHOW COLUMNS FROM articles LIKE 'page_breaks'");
    if ($checkCol->rowCount() == 0) {
        // Add page_breaks column to articles table
        $pdo->exec("ALTER TABLE articles 
            ADD COLUMN page_breaks JSON DEFAULT NULL 
            COMMENT 'JSON array of page break positions and titles'");
        echo "Added page_breaks column to articles table\n";
    } else {
        echo "page_breaks column already exists in articles\n";
    }
    
    $checkCol = $pdo->query("SHOW COLUMNS FROM articles LIKE 'page_count'");
    if ($checkCol->rowCount() == 0) {
        // Add page_count column for quick reference
        $pdo->exec("ALTER TABLE articles 
            ADD COLUMN page_count INT DEFAULT 1 
            COMMENT 'Total number of pages in content'");
        echo "Added page_count column to articles table\n";
    } else {
        echo "page_count column already exists in articles\n";
    }
    
    // Update existing articles with page information
    $stmt = $pdo->query("SELECT id, content FROM articles WHERE content IS NOT NULL");
    $updated = 0;
    
    while ($row = $stmt->fetch()) {
        $pageData = extractPageInfo($row['content']);
        
        $updateStmt = $pdo->prepare("UPDATE articles 
            SET page_breaks = ?, page_count = ? 
            WHERE id = ?");
        
        $updateStmt->execute([
            json_encode($pageData['pages']),
            $pageData['count'],
            $row['id']
        ]);
        
        if ($pageData['count'] > 1) {
            $updated++;
        }
    }
    
    echo "Updated page tracking for $updated multi-page articles\n";
    
    // Add index
    try {
        $pdo->exec("ALTER TABLE articles ADD INDEX idx_page_count (page_count)");
        echo "Added index on page_count\n";
    } catch (Exception $e) {
        echo "Index might already exist, continuing...\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}