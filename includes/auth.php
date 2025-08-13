<?php
/**
 * Authentication and Authorization Class
 * 
 * Handles user authentication, session management, and role-based access control.
 * Provides methods for login, logout, password management, and permission checks.
 * Uses secure password hashing with PHP's password_hash/verify functions.
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
declare(strict_types=1);

/**
 * Static authentication utility class
 */
class Auth {
    /**
     * Authenticate user and create session
     * 
     * Validates credentials against database, creates session on success.
     * Regenerates session ID for security, updates last login timestamp.
     * 
     * @param string $username Username to authenticate
     * @param string $password Plain text password to verify
     * @return bool True if authentication successful, false otherwise
     */
    public static function login(string $username, string $password): bool {
        require_once __DIR__ . '/database.php';
        $pdo = Database::getInstance();
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            session_regenerate_id(true);
            
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);
            return true;
        }
        return false;
    }
    
    /**
     * Destroy user session and logout
     * 
     * Clears all session data, destroys session, and regenerates ID.
     * Ensures complete logout with new clean session.
     * 
     * @return void
     */
    public static function logout(): void {
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Check if user is currently logged in
     * 
     * Verifies presence of user_id in session.
     * 
     * @return bool True if user is logged in, false otherwise
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Require admin role or redirect to login
     * 
     * Checks if user is logged in with admin role.
     * Redirects to login page if not authorized.
     * Should be called at the top of admin-only pages.
     * 
     * @return void
     */
    public static function requireAdmin(): void {
        session_start();
        if (!self::isLoggedIn() || $_SESSION['role'] !== 'admin') {
            header('Location: /admin/login.php');
            exit;
        }
    }
    
    /**
     * Get current logged-in user's ID
     * 
     * @return int|null User ID if logged in, null otherwise
     */
    public static function getUserId(): ?int {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
    
    /**
     * Verify password for a specific user
     * 
     * Used for password confirmation dialogs.
     * Does not create a session, only validates credentials.
     * 
     * @param string $username Username to check
     * @param string $password Plain text password to verify
     * @return bool True if password is correct, false otherwise
     */
    public static function checkPassword(string $username, string $password): bool {
        require_once __DIR__ . '/database.php';
        $pdo = Database::getInstance();
        
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $hash = $stmt->fetchColumn();
        
        return $hash && password_verify($password, $hash);
    }
    
    /**
     * Update user's password
     * 
     * Hashes new password and updates database.
     * Used for password change functionality.
     * 
     * @param int $userId ID of user to update
     * @param string $newPassword New plain text password
     * @return bool True if update successful, false otherwise
     */
    public static function updatePassword(int $userId, string $newPassword): bool {
        require_once __DIR__ . '/database.php';
        $pdo = Database::getInstance();
        
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }
}