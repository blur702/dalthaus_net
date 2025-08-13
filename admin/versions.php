<?php
declare(strict_types=1);
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$pageTitle = 'Version History';
$pdo = Database::getInstance();

$contentId = (int)($_GET['content_id'] ?? 0);

// If no content_id, show list of all content with versions
if (!$contentId) {
    // Get all content with version counts
    $allContent = $pdo->query("
        SELECT c.*, COUNT(cv.id) as version_count, MAX(cv.created_at) as last_version_date
        FROM content c
        LEFT JOIN content_versions cv ON c.id = cv.content_id
        WHERE c.deleted_at IS NULL
        GROUP BY c.id
        HAVING version_count > 0
        ORDER BY last_version_date DESC
    ")->fetchAll();
    
    $pageTitle = 'All Version History';
    $showList = true;
} else {
    // Get specific content details
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ?");
    $stmt->execute([$contentId]);
    $content = $stmt->fetch();
    
    if (!$content) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    $showList = false;
}

// Handle version restoration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_version'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token';
    } else {
        $versionId = (int)$_POST['version_id'];
        
        // Get version details
        $stmt = $pdo->prepare("SELECT * FROM content_versions WHERE id = ? AND content_id = ?");
        $stmt->execute([$versionId, $contentId]);
        $version = $stmt->fetch();
        
        if ($version) {
            try {
                $pdo->beginTransaction();
                
                // Create backup of current version
                $stmt = $pdo->prepare("
                    INSERT INTO content_versions (content_id, version_number, title, body, created_at, is_autosave)
                    VALUES (?, (SELECT COALESCE(MAX(version_number), 0) + 1 FROM content_versions cv WHERE cv.content_id = ?), ?, ?, NOW(), FALSE)
                ");
                $stmt->execute([$contentId, $contentId, $content['title'], $content['body']]);
                
                // Restore selected version to content
                $stmt = $pdo->prepare("
                    UPDATE content 
                    SET title = ?, body = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$version['title'], $version['body'], $contentId]);
                
                $pdo->commit();
                cacheClear();
                
                $_SESSION['success'] = 'Version restored successfully';
                header("Location: /admin/versions.php?content_id=$contentId");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Failed to restore version';
                logMessage('Version restore failed: ' . $e->getMessage(), 'error');
            }
        }
    }
}

// Handle version deletion (for autosaves)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_version'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid CSRF token';
    } else {
        $versionId = (int)$_POST['version_id'];
        
        // Only allow deletion of autosaves
        $stmt = $pdo->prepare("DELETE FROM content_versions WHERE id = ? AND content_id = ? AND is_autosave = TRUE");
        $stmt->execute([$versionId, $contentId]);
        
        $_SESSION['success'] = 'Autosave deleted';
        header("Location: /admin/versions.php?content_id=$contentId");
        exit;
    }
}

// Get all versions if viewing specific content
if (!$showList) {
    $stmt = $pdo->prepare("
        SELECT * FROM content_versions 
        WHERE content_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$contentId]);
    $versions = $stmt->fetchAll();
    
    $pageTitle = 'Version History: ' . $content['title'];
}

$extraStyles = '
    <style>
        .version-container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        .version-list {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 1rem;
        }
        .version-list th {
            background: #f8f9fa;
            padding: 0.75rem;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .version-list td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }
        .version-list tr:hover {
            background: #f8f9fa;
        }
        .content-info {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .content-info h3 {
            margin-top: 0;
        }
        .current-version {
            background: white;
            border: 2px solid #28a745;
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .current-label {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 3px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .version-list {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        .version-item {
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            position: relative;
        }
        .version-item:last-child {
            border-bottom: none;
        }
        .version-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .version-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .version-number {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .version-date {
            color: #666;
            font-size: 0.9rem;
        }
        .version-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .badge-autosave {
            background: #fff3cd;
            color: #856404;
        }
        .badge-manual {
            background: #d1ecf1;
            color: #0c5460;
        }
        .version-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-compare {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-restore {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 3px;
            cursor: pointer;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 3px;
            cursor: pointer;
        }
        .version-preview {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            display: none;
        }
        .version-preview.active {
            display: block;
        }
        .preview-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .preview-content {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 3px;
            max-height: 200px;
            overflow-y: auto;
        }
        .toggle-preview {
            background: transparent;
            border: 1px solid #666;
            color: #666;
            padding: 0.25rem 0.75rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>';

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/nav.php';
?>

    <main class="admin-content">
        <div class="version-container">
            <?php if ($showList): ?>
                <h2>All Version History</h2>
                
                <?php if (empty($allContent)): ?>
                    <p>No content with versions found.</p>
                <?php else: ?>
                    <table class="version-list">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Versions</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allContent as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['title']) ?></td>
                                    <td><?= ucfirst($item['type']) ?></td>
                                    <td><?= ucfirst($item['status']) ?></td>
                                    <td><?= $item['version_count'] ?></td>
                                    <td><?= date('M d, Y', strtotime($item['updated_at'])) ?></td>
                                    <td>
                                        <a href="/admin/versions.php?content_id=<?= $item['id'] ?>" class="btn btn-sm">View Versions</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <a href="/admin/<?= $content['type'] ?>s.php" class="back-link">← Back to <?= ucfirst($content['type']) ?>s</a>
                
                <h2>Version History</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <div class="content-info">
                <h3><?= htmlspecialchars($content['title']) ?></h3>
                <p>Type: <strong><?= ucfirst($content['type']) ?></strong></p>
                <p>Status: <strong><?= ucfirst($content['status']) ?></strong></p>
                <p>Last Updated: <strong><?= date('M d, Y g:i A', strtotime($content['updated_at'])) ?></strong></p>
            </div>
            
            <div class="current-version">
                <span class="current-label">CURRENT VERSION</span>
                <div class="preview-title">Title: <?= htmlspecialchars($content['title']) ?></div>
                <div class="preview-content">
                    <?= strip_tags($content['body'] ?? '', '<p><br><strong><em><ul><ol><li>') ?>
                </div>
            </div>
            
            <?php if (empty($versions)): ?>
                <p>No version history available.</p>
            <?php else: ?>
                <h3>Previous Versions (<?= count($versions) ?>)</h3>
                <div class="version-list">
                    <?php foreach ($versions as $index => $version): ?>
                        <div class="version-item">
                            <div class="version-header">
                                <div class="version-meta">
                                    <span class="version-number">Version #<?= $version['version_number'] ?></span>
                                    <span class="version-date"><?= date('M d, Y g:i A', strtotime($version['created_at'])) ?></span>
                                    <span class="version-badge <?= $version['is_autosave'] ? 'badge-autosave' : 'badge-manual' ?>">
                                        <?= $version['is_autosave'] ? 'Autosave' : 'Manual Save' ?>
                                    </span>
                                </div>
                                <div class="version-actions">
                                    <button class="toggle-preview" onclick="togglePreview(<?= $index ?>)">
                                        Preview
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="version_id" value="<?= $version['id'] ?>">
                                        <button type="submit" name="restore_version" class="btn-restore" 
                                                onclick="return confirm('Restore this version? Current content will be backed up.')">
                                            Restore
                                        </button>
                                    </form>
                                    <?php if ($version['is_autosave']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="version_id" value="<?= $version['id'] ?>">
                                            <button type="submit" name="delete_version" class="btn-delete" 
                                                    onclick="return confirm('Delete this autosave?')">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div id="preview-<?= $index ?>" class="version-preview">
                                <div class="preview-title">Title: <?= htmlspecialchars($version['title'] ?? '') ?></div>
                                <div class="preview-content">
                                    <?= strip_tags($version['body'] ?? '', '<p><br><strong><em><ul><ol><li>') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function togglePreview(index) {
            const preview = document.getElementById('preview-' + index);
            if (preview) {
                preview.classList.toggle('active');
            }
        }
    </script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>