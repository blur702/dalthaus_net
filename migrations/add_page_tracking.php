<?php
/**
 * Migration: Add page tracking for content with page breaks
 * 
 * Adds columns to track page breaks and page titles for multi-page content.
 * This enables creation of page navigation menus and table of contents.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

try {
    $pdo = Database::getInstance();
    
    echo "Starting migration: Add page tracking...\n";
    
    // Check if columns already exist
    $checkCol = $pdo->query("SHOW COLUMNS FROM content LIKE 'page_breaks'");
    if ($checkCol->rowCount() == 0) {
        // Add page_breaks column to content table
        // This will store JSON data about page breaks and titles
        $pdo->exec("ALTER TABLE content 
            ADD COLUMN page_breaks JSON DEFAULT NULL 
            COMMENT 'JSON array of page break positions and titles'");
        echo "Added page_breaks column to content table\n";
    } else {
        echo "page_breaks column already exists\n";
    }
    
    $checkCol = $pdo->query("SHOW COLUMNS FROM content LIKE 'page_count'");
    if ($checkCol->rowCount() == 0) {
        // Add page_count column for quick reference
        $pdo->exec("ALTER TABLE content 
            ADD COLUMN page_count INT DEFAULT 1 
            COMMENT 'Total number of pages in content'");
        echo "Added page_count column to content table\n";
    } else {
        echo "page_count column already exists\n";
    }
    
    // Create a function to extract page information from existing content
    $stmt = $pdo->query("SELECT id, body, type FROM content WHERE body IS NOT NULL");
    $updated = 0;
    
    while ($row = $stmt->fetch()) {
        $pages = explode('<!-- page -->', $row['body']);
        $pageCount = count($pages);
        
        if ($pageCount > 1) {
            // Extract page titles (first heading or first text)
            $pageInfo = [];
            foreach ($pages as $index => $pageContent) {
                $pageNum = $index + 1;
                $title = '';
                
                // Try to extract first heading as title
                if (preg_match('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i', $pageContent, $matches)) {
                    $title = strip_tags($matches[1]);
                } elseif (preg_match('/<p[^>]*>(.*?)<\/p>/i', $pageContent, $matches)) {
                    // Fall back to first paragraph (truncated)
                    $title = substr(strip_tags($matches[1]), 0, 50);
                    if (strlen(strip_tags($matches[1])) > 50) {
                        $title .= '...';
                    }
                }
                
                // Default title if nothing found
                if (empty(trim($title))) {
                    $title = "Page $pageNum";
                }
                
                $pageInfo[] = [
                    'page' => $pageNum,
                    'title' => trim($title),
                    'position' => $index > 0 ? strpos($row['body'], '<!-- page -->', ($index - 1) * 13) : 0
                ];
            }
            
            // Update the content record
            $updateStmt = $pdo->prepare("UPDATE content 
                SET page_breaks = ?, page_count = ? 
                WHERE id = ?");
            $updateStmt->execute([
                json_encode($pageInfo),
                $pageCount,
                $row['id']
            ]);
            $updated++;
        } else {
            // Single page content
            $updateStmt = $pdo->prepare("UPDATE content 
                SET page_count = 1 
                WHERE id = ?");
            $updateStmt->execute([$row['id']]);
        }
    }
    
    echo "Updated page tracking for $updated multi-page content items\n";
    
    // Add indexes for better query performance
    $pdo->exec("ALTER TABLE content ADD INDEX idx_page_count (page_count)");
    echo "Added index on page_count\n";
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}