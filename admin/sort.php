<?php
declare(strict_types=1);
session_start();
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

Auth::requireAdmin();
$pdo = Database::getInstance();

// Handle AJAX sort update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
    
    $type = $_POST['type'] ?? '';
    $order = json_decode($_POST['order'] ?? '[]', true);
    
    if (!in_array($type, ['article', 'photobook']) || !is_array($order)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        foreach ($order as $index => $id) {
            $stmt = $pdo->prepare("UPDATE content SET sort_order = ? WHERE id = ? AND type = ?");
            $stmt->execute([$index, $id, $type]);
        }
        
        $pdo->commit();
        cacheClear();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update order']);
    }
    exit;
}

// Get content for sorting
$contentType = $_GET['type'] ?? 'article';
if (!in_array($contentType, ['article', 'photobook'])) {
    $contentType = 'article';
}

$stmt = $pdo->prepare("
    SELECT id, title, status, sort_order 
    FROM content 
    WHERE type = ? AND deleted_at IS NULL 
    ORDER BY sort_order ASC, created_at DESC
");
$stmt->execute([$contentType]);
$items = $stmt->fetchAll();

$pageTitle = ucfirst($contentType) . ' Ordering';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .sort-container {
            max-width: 800px;
            margin: 2rem auto;
        }
        .sort-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #ddd;
        }
        .sort-tab {
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            color: #333;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .sort-tab:hover {
            background: #f5f5f5;
        }
        .sort-tab.active {
            color: #0066cc;
            border-bottom-color: #0066cc;
        }
        .sortable-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sortable-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            cursor: move;
            transition: all 0.3s;
        }
        .sortable-item:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sortable-item.dragging {
            opacity: 0.5;
        }
        .sort-handle {
            margin-right: 1rem;
            color: #999;
            cursor: grab;
        }
        .sort-handle:active {
            cursor: grabbing;
        }
        .item-title {
            flex: 1;
            font-weight: 500;
        }
        .item-status {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .status-published {
            background: #d4edda;
            color: #155724;
        }
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        .save-indicator {
            position: fixed;
            top: 80px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            display: none;
            z-index: 1000;
        }
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .instructions {
            background: #f8f9fa;
            border-left: 4px solid #0066cc;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Admin Panel</h1>
        <nav>
            <a href="/admin/dashboard">Dashboard</a>
            <a href="/admin/articles">Articles</a>
            <a href="/admin/photobooks">Photobooks</a>
            <a href="/admin/menus">Menus</a>
            <a href="/admin/sort" class="active">Sort</a>
            <a href="/admin/import">Import</a>
            <a href="/admin/upload">Upload</a>
            <a href="/admin/logout">Logout</a>
        </nav>
    </header>

    <main class="admin-content">
        <div class="sort-container">
            <h2><?= htmlspecialchars($pageTitle) ?></h2>
            
            <div class="sort-tabs">
                <a href="?type=article" class="sort-tab <?= $contentType === 'article' ? 'active' : '' ?>">
                    Articles
                </a>
                <a href="?type=photobook" class="sort-tab <?= $contentType === 'photobook' ? 'active' : '' ?>">
                    Photobooks
                </a>
            </div>
            
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <p>No <?= htmlspecialchars($contentType) ?>s found.</p>
                    <a href="/admin/<?= htmlspecialchars($contentType) ?>s" class="btn btn-primary">
                        Create <?= htmlspecialchars(ucfirst($contentType)) ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="instructions">
                    <strong>Instructions:</strong> Drag and drop items to reorder them. Changes are saved automatically.
                </div>
                
                <ul id="sortable-list" class="sortable-list">
                    <?php foreach ($items as $item): ?>
                        <li class="sortable-item" data-id="<?= $item['id'] ?>">
                            <span class="sort-handle">≡</span>
                            <span class="item-title"><?= htmlspecialchars($item['title']) ?></span>
                            <span class="item-status status-<?= htmlspecialchars($item['status']) ?>">
                                <?= htmlspecialchars($item['status']) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <div id="save-indicator" class="save-indicator">Order saved!</div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        const sortableList = document.getElementById('sortable-list');
        const saveIndicator = document.getElementById('save-indicator');
        const contentType = '<?= $contentType ?>';
        const csrfToken = '<?= generateCSRFToken() ?>';
        
        if (sortableList) {
            const sortable = Sortable.create(sortableList, {
                handle: '.sort-handle',
                animation: 150,
                ghostClass: 'dragging',
                onEnd: function(evt) {
                    // Get the new order
                    const order = Array.from(sortableList.children).map(item => item.dataset.id);
                    
                    // Send update to server
                    const formData = new FormData();
                    formData.append('ajax', '1');
                    formData.append('type', contentType);
                    formData.append('order', JSON.stringify(order));
                    formData.append('csrf_token', csrfToken);
                    
                    fetch('/admin/sort', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show save indicator
                            saveIndicator.style.display = 'block';
                            setTimeout(() => {
                                saveIndicator.style.display = 'none';
                            }, 2000);
                        } else {
                            alert('Failed to save order. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to save order. Please try again.');
                    });
                }
            });
        }
    </script>
</body>
</html>