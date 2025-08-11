<?php
declare(strict_types=1);

define('TEST_MODE', true);
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/database.php';

// Setup test database
Database::setup();

if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === '--seed') {
    seedTestData();
}

function seedTestData(): void {
    $pdo = Database::getInstance();
    $pdo->exec("USE " . TEST_DATABASE);
    
    // Add test data
    $pdo->exec("INSERT INTO content (type, title, slug, body, status) VALUES 
        ('article', 'Test Article', 'test-article', '<p>Test content</p>', 'published'),
        ('photobook', 'Test Photobook', 'test-photobook', '<p>Photo content</p>', 'published')
    ");
    
    echo "Test data seeded\n";
}

function teardownTestDatabase(): void {
    $pdo = Database::getInstance();
    $pdo->exec("DROP DATABASE IF EXISTS " . TEST_DATABASE);
}