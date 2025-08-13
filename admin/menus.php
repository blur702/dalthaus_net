<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireAdmin();

$pageTitle = 'Menus';
$pdo = Database::getInstance();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'add':
                $contentId = (int)$_POST['content_id'];
                $location = $_POST['location'];
                
                // Get max sort order
                $stmt = $pdo->prepare("SELECT MAX(sort_order) FROM menus WHERE location = ?");
                $stmt->execute([$location]);
                $maxOrder = $stmt->fetchColumn() ?? 0;
                
                $stmt = $pdo->prepare("
                    INSERT INTO menus (location, content_id, sort_order, is_active) 
                    VALUES (?, ?, ?, TRUE)
                ");
                if ($stmt->execute([$location, $contentId, $maxOrder + 1])) {
                    $message = 'Menu item added';
                    cacheClear();
                }
                break;
                
            case 'remove':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = 'Menu item removed';
                    cacheClear();
                }
                break;
                
            case 'toggle':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE menus SET is_active = NOT is_active WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $message = 'Menu item toggled';
                    cacheClear();
                }
                break;
        }
    }
}

// Get all published content for dropdown
$availableContent = $pdo->query("
    SELECT id, title, type FROM content 
    WHERE status = 'published' AND deleted_at IS NULL 
    ORDER BY type, title
")->fetchAll();

// Get top menu items
$topMenu = $pdo->query("
    SELECT m.*, c.title, c.type 
    FROM menus m
    JOIN content c ON m.content_id = c.id
    WHERE m.location = 'top'
    ORDER BY m.sort_order
")->fetchAll();

// Get bottom menu items
$bottomMenu = $pdo->query("
    SELECT m.*, c.title, c.type 
    FROM menus m
    JOIN content c ON m.content_id = c.id
    WHERE m.location = 'bottom'
    ORDER BY m.sort_order
")->fetchAll();

$csrf = generateCSRFToken();

$extraStyles = '<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>';

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/nav.php';
?>
        
        <main class="admin-content">
            <h2>Manage Menus</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <div class="menu-add-form">
                <h3>Add Menu Item</h3>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="content_id">Content</label>
                        <select id="content_id" name="content_id" required>
                            <option value="">Select content...</option>
                            <?php foreach ($availableContent as $content): ?>
                            <option value="<?= $content['id'] ?>">
                                <?= htmlspecialchars($content['title']) ?> (<?= $content['type'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <select id="location" name="location" required>
                            <option value="top">Top Menu</option>
                            <option value="bottom">Bottom Menu</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add to Menu</button>
                </form>
            </div>
            
            <div class="menu-section">
                <h3>Top Menu</h3>
                <ul id="top-menu-list" class="sortable-list" data-location="top">
                    <?php foreach ($topMenu as $item): ?>
                    <li class="sortable-item" data-id="<?= $item['id'] ?>">
                        <span class="sortable-handle">☰</span>
                        <span class="menu-title">
                            <?= htmlspecialchars($item['title']) ?>
                            <small>(<?= $item['type'] ?>)</small>
                        </span>
                        <span class="menu-actions">
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-sm">
                                    <?= $item['is_active'] ? 'Disable' : 'Enable' ?>
                                </button>
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="menu-section">
                <h3>Bottom Menu</h3>
                <ul id="bottom-menu-list" class="sortable-list" data-location="bottom">
                    <?php foreach ($bottomMenu as $item): ?>
                    <li class="sortable-item" data-id="<?= $item['id'] ?>">
                        <span class="sortable-handle">☰</span>
                        <span class="menu-title">
                            <?= htmlspecialchars($item['title']) ?>
                            <small>(<?= $item['type'] ?>)</small>
                        </span>
                        <span class="menu-actions">
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-sm">
                                    <?= $item['is_active'] ? 'Disable' : 'Enable' ?>
                                </button>
                            </form>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </main>
    
<?php
$extraScripts = '<script src="/assets/js/sorting.js"></script>';
require_once __DIR__ . '/templates/footer.php';
?>