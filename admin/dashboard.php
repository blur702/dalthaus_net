<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireAdmin();

$pageTitle = 'Dashboard';

// Check if password needs rotation
$needsPasswordChange = false;
if ($_SESSION['username'] === DEFAULT_ADMIN_USER) {
    if (Auth::checkPassword(DEFAULT_ADMIN_USER, DEFAULT_ADMIN_PASS)) {
        $needsPasswordChange = true;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_password'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        if (Auth::updatePassword(Auth::getUserId(), $_POST['new_password'])) {
            $needsPasswordChange = false;
            $_SESSION['message'] = 'Password updated successfully';
        }
    }
}

// Get stats
$pdo = Database::getInstance();
$stats = [
    'articles' => $pdo->query("SELECT COUNT(*) FROM content WHERE type='article' AND deleted_at IS NULL")->fetchColumn(),
    'photobooks' => $pdo->query("SELECT COUNT(*) FROM content WHERE type='photobook' AND deleted_at IS NULL")->fetchColumn(),
    'versions' => $pdo->query("SELECT COUNT(*) FROM content_versions")->fetchColumn()
];

$csrf = generateCSRFToken();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/nav.php';
?>
        
        <main class="admin-content">
            <h2>Dashboard</h2>
            
            <?php if ($needsPasswordChange): ?>
            <div class="password-rotation-prompt alert alert-warning">
                <h3>Password Change Required</h3>
                <p>You are still using the default password. Please change it now.</p>
                <form method="post">
                    <input type="password" name="new_password" placeholder="New Password" required minlength="8">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit">Update Password</button>
                </form>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <?php unset($_SESSION['message']); ?>
            </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Articles</h3>
                    <p class="stat-number"><?= $stats['articles'] ?></p>
                    <a href="/admin/articles.php">Manage Articles</a>
                </div>
                <div class="stat-card">
                    <h3>Photobooks</h3>
                    <p class="stat-number"><?= $stats['photobooks'] ?></p>
                    <a href="/admin/photobooks.php">Manage Photobooks</a>
                </div>
                <div class="stat-card">
                    <h3>Versions</h3>
                    <p class="stat-number"><?= $stats['versions'] ?></p>
                    <a href="/admin/versions.php">View History</a>
                </div>
            </div>
        </main>
<?php require_once __DIR__ . '/templates/footer.php'; ?>