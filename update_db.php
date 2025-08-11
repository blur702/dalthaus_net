<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$pdo = Database::getInstance();
$pdo->exec('USE dalthaus_cms');

// Add author and published_at columns if they don't exist
try {
    $pdo->exec("ALTER TABLE content ADD COLUMN author VARCHAR(100) DEFAULT 'Don Althaus' AFTER slug");
    echo "✓ Added author column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "- Author column already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE content ADD COLUMN published_at TIMESTAMP NULL AFTER deleted_at");
    echo "✓ Added published_at column\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "- Published_at column already exists\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

// Update existing content to set published_at from created_at for published items
$pdo->exec("UPDATE content SET published_at = created_at WHERE status = 'published' AND published_at IS NULL");
echo "✓ Updated published dates for existing content\n";

// Set some specific dates for demo content
$pdo->exec("UPDATE content SET published_at = '2024-01-14', author = 'Don Althaus' WHERE slug = 'storytellers-legacy'");
$pdo->exec("UPDATE content SET published_at = '2024-08-10', author = 'Don Althaus' WHERE slug = 'welcome'");

echo "\n✓ Database updated successfully!\n";