<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase {
    
    public function testDatabaseConnection(): void {
        $pdo = Database::getInstance();
        $this->assertInstanceOf(PDO::class, $pdo);
    }
    
    public function testDatabaseSetup(): void {
        Database::setup();
        $pdo = Database::getInstance();
        $pdo->exec("USE " . TEST_DATABASE);
        
        // Check tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $this->assertContains('users', $tables);
        $this->assertContains('content', $tables);
        $this->assertContains('content_versions', $tables);
        $this->assertContains('menus', $tables);
        $this->assertContains('attachments', $tables);
        $this->assertContains('settings', $tables);
        $this->assertContains('sessions', $tables);
    }
    
    public function testDefaultAdminSeeded(): void {
        Database::setup();
        $pdo = Database::getInstance();
        $pdo->exec("USE " . TEST_DATABASE);
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([DEFAULT_ADMIN_USER]);
        $user = $stmt->fetch();
        
        $this->assertNotEmpty($user);
        $this->assertEquals('admin', $user['role']);
        $this->assertTrue(password_verify(DEFAULT_ADMIN_PASS, $user['password_hash']));
    }
}