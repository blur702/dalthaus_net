<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

Auth::requireAdmin();

$pageTitle = 'Upload Files';
$pdo = Database::getInstance();
$message = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } elseif (isset($_FILES['file'])) {
        $contentId = (int)($_POST['content_id'] ?? 0);
        $uploader = new FileUploader();
        
        try {
            $result = $uploader->upload($_FILES['file']);
            
            if ($result['success']) {
                // Save to database
                $stmt = $pdo->prepare("
                    INSERT INTO attachments (content_id, filename, original_name, mime_type, size) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $contentId ?: null,
                    $result['filename'],
                    $result['original_name'],
                    $result['mime_type'],
                    $result['size']
                ]);
                
                $message = 'File uploaded successfully';
            } else {
                $error = $result['error'];
            }
        } catch (Exception $e) {
            $error = 'Upload failed: ' . $e->getMessage();
        }
    }
}

// Get recent uploads
$uploads = $pdo->query("
    SELECT a.*, c.title as content_title 
    FROM attachments a
    LEFT JOIN content c ON a.content_id = c.id
    ORDER BY a.created_at DESC
    LIMIT 50
")->fetchAll();

// Get content for linking
$content = $pdo->query("
    SELECT id, title, type FROM content 
    WHERE deleted_at IS NULL 
    ORDER BY type, title
")->fetchAll();

$csrf = generateCSRFToken();

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/nav.php';
?>
        
        <main class="admin-content">
            <h2>Upload Files</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="upload-form">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    
                    <div class="form-group">
                        <label for="file">Select File</label>
                        <input type="file" id="file" name="file" required>
                        <small>Max size: <?= UPLOAD_MAX_SIZE / 1048576 ?> MB. Allowed types: <?= implode(', ', ALLOWED_EXTENSIONS) ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="content_id">Link to Content (Optional)</label>
                        <select id="content_id" name="content_id">
                            <option value="">None</option>
                            <?php foreach ($content as $item): ?>
                            <option value="<?= $item['id'] ?>">
                                <?= htmlspecialchars($item['title']) ?> (<?= $item['type'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>
            
            <h3>Recent Uploads</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Linked To</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uploads as $upload): ?>
                    <tr>
                        <td><?= htmlspecialchars($upload['original_name']) ?></td>
                        <td><?= number_format($upload['size'] / 1024, 1) ?> KB</td>
                        <td><?= htmlspecialchars($upload['mime_type']) ?></td>
                        <td>
                            <?php if ($upload['content_title']): ?>
                                <?= htmlspecialchars($upload['content_title']) ?>
                            <?php else: ?>
                                <em>None</em>
                            <?php endif; ?>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($upload['created_at'])) ?></td>
                        <td>
                            <a href="/download/<?= htmlspecialchars($upload['filename']) ?>" class="btn btn-sm" target="_blank">View</a>
                            <button onclick="copyLink('/download/<?= htmlspecialchars($upload['filename']) ?>')" class="btn btn-sm">Copy Link</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    
    <script>
        function copyLink(link) {
            const fullLink = window.location.origin + link;
            navigator.clipboard.writeText(fullLink).then(() => {
                alert('Link copied to clipboard');
            });
        }
    </script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>