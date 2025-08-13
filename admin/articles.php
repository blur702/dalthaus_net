<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/page_tracker.php';

Auth::requireAdmin();

$pageTitle = 'Articles';
$pdo = Database::getInstance();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
            case 'update':
                $title = sanitizeInput($_POST['title'] ?? '');
                $body = $_POST['body'] ?? '';
                $status = $_POST['status'] ?? 'draft';
                $slug = $_POST['slug'] ?? createSlug($title);
                $author = sanitizeInput($_POST['author'] ?? 'Don Althaus');
                
                if ($action === 'create') {
                    $stmt = $pdo->prepare("
                        INSERT INTO content (type, title, slug, author, body, status, published_at) 
                        VALUES ('article', ?, ?, ?, ?, ?, ?)
                    ");
                    $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
                    if ($stmt->execute([$title, $slug, $author, $body, $status, $publishedAt])) {
                        $contentId = $pdo->lastInsertId();
                        
                        // Update page tracking
                        updatePageTracking($pdo, $contentId, $body);
                        
                        // Create initial version
                        $stmt = $pdo->prepare("
                            INSERT INTO content_versions (content_id, version_number, title, body) 
                            VALUES (?, 1, ?, ?)
                        ");
                        $stmt->execute([$contentId, $title, $body]);
                        $message = 'Article created successfully';
                    }
                } else {
                    $id = (int)$_POST['id'];
                    // Check if we're publishing for the first time
                    $existingStatus = $pdo->query("SELECT status, published_at FROM content WHERE id = $id")->fetch();
                    $publishedAt = null;
                    if ($status === 'published' && $existingStatus['status'] === 'draft' && !$existingStatus['published_at']) {
                        $publishedAt = date('Y-m-d H:i:s');
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE content 
                        SET title = ?, slug = ?, author = ?, body = ?, status = ?" . 
                        ($publishedAt ? ", published_at = ?" : "") . "
                        WHERE id = ? AND type = 'article'
                    ");
                    $params = [$title, $slug, $author, $body, $status];
                    if ($publishedAt) $params[] = $publishedAt;
                    $params[] = $id;
                    if ($stmt->execute($params)) {
                        // Update page tracking
                        updatePageTracking($pdo, $id, $body);
                        
                        // Create new version
                        $versionNum = $pdo->query("SELECT MAX(version_number) FROM content_versions WHERE content_id = $id")->fetchColumn() + 1;
                        $stmt = $pdo->prepare("
                            INSERT INTO content_versions (content_id, version_number, title, body, is_autosave) 
                            VALUES (?, ?, ?, ?, FALSE)
                        ");
                        $stmt->execute([$id, $versionNum, $title, $body]);
                        $message = 'Article updated successfully';
                        cacheClear();
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE content SET deleted_at = NOW() WHERE id = ? AND type = 'article'");
                if ($stmt->execute([$id])) {
                    $message = 'Article moved to trash';
                    cacheClear();
                }
                break;
                
            case 'restore':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE content SET deleted_at = NULL WHERE id = ? AND type = 'article'");
                if ($stmt->execute([$id])) {
                    $message = 'Article restored';
                }
                break;
        }
    }
}

// Get articles
$showTrash = isset($_GET['trash']);
$query = "SELECT * FROM content WHERE type = 'article'";
if ($showTrash) {
    $query .= " AND deleted_at IS NOT NULL";
} else {
    $query .= " AND deleted_at IS NULL";
}
$query .= " ORDER BY created_at DESC";
$articles = $pdo->query($query)->fetchAll();

// Get article for editing if ID provided
$editArticle = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ? AND type = 'article'");
    $stmt->execute([$_GET['edit']]);
    $editArticle = $stmt->fetch();
}

$csrf = generateCSRFToken();

$extraStyles = '<script src="/assets/vendor/tinymce.min.js"></script>';

require_once __DIR__ . '/templates/header.php';
require_once __DIR__ . '/templates/nav.php';
?>
        
        <main class="admin-content">
            <div class="page-header">
                <h2>Articles</h2>
                <div class="actions">
                    <button onclick="showCreateForm()" class="btn btn-primary">New Article</button>
                    <a href="?trash" class="btn btn-secondary">View Trash</a>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div id="article-form" style="<?= $editArticle ? '' : 'display:none' ?>">
                <h3><?= $editArticle ? 'Edit Article' : 'Create Article' ?></h3>
                <form method="post" id="editor-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="<?= $editArticle ? 'update' : 'create' ?>">
                    <?php if ($editArticle): ?>
                    <input type="hidden" name="id" value="<?= $editArticle['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?= $editArticle ? htmlspecialchars($editArticle['title']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">URL Alias</label>
                        <input type="text" id="slug" name="slug" placeholder="e.g., my-article-title" value="<?= $editArticle ? htmlspecialchars($editArticle['slug']) : '' ?>">
                        <small class="form-help">Used in the URL. Leave blank to auto-generate from title.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?= $editArticle ? htmlspecialchars($editArticle['author'] ?? 'Don Althaus') : 'Don Althaus' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="document-upload">Import Document (Optional)</label>
                        <input type="file" id="document-upload" accept=".docx,.doc,.pdf">
                        <small class="form-help">Upload a Word document or PDF to convert to HTML content</small>
                        <div id="upload-status"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="body">Content</label>
                        <textarea id="body" name="body" rows="20"><?= $editArticle ? $editArticle['body'] : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?= $editArticle && $editArticle['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $editArticle && $editArticle['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="/admin/articles.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>URL Alias</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $article): ?>
                    <tr>
                        <td><?= htmlspecialchars($article['title']) ?></td>
                        <td><?= htmlspecialchars($article['slug']) ?></td>
                        <td><span class="status-<?= $article['status'] ?>"><?= $article['status'] ?></span></td>
                        <td><?= date('Y-m-d', strtotime($article['created_at'])) ?></td>
                        <td>
                            <?php if ($showTrash): ?>
                                <form method="post" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="id" value="<?= $article['id'] ?>">
                                    <button type="submit" class="btn btn-sm">Restore</button>
                                </form>
                            <?php else: ?>
                                <a href="?edit=<?= $article['id'] ?>" class="btn btn-sm">Edit</a>
                                <a href="/admin/versions.php?content_id=<?= $article['id'] ?>" class="btn btn-sm">Versions</a>
                                <form method="post" style="display:inline" onsubmit="return confirm('Move to trash?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $article['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    
    <script src="/assets/js/autosave.js"></script>
    <script>
        tinymce.init({
            selector: '#body',
            height: 500,
            menubar: false,
            license_key: 'gpl',
            plugins: 'link image lists pagebreak preview code',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | pagebreak | preview code',
            promotion: false,
            branding: false,
            content_css: 'https://fonts.googleapis.com/css2?family=Gelasio:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&family=Arimo:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap',
            content_style: `
                body { 
                    font-family: 'Gelasio', serif; 
                    font-size: 1.1rem;
                    line-height: 1.8; 
                    color: #333;
                    max-width: 100%;
                    padding: 1rem;
                }
                h1, h2, h3, h4, h5, h6 {
                    font-family: 'Arimo', sans-serif;
                    font-weight: 600;
                    line-height: 1.3;
                    color: #2c3e50;
                }
                h1 { font-size: 2rem; }
                h2 { font-size: 1.75rem; }
                h3 { font-size: 1.5rem; }
                h4 { font-size: 1.25rem; }
                p {
                    margin-bottom: 1.5rem;
                }
                img { 
                    max-width: 100%; 
                    height: auto; 
                    margin: 2rem 0; 
                    border-radius: 8px;
                    display: block;
                }
                a {
                    color: #3498db;
                    text-decoration: none;
                }
                a:hover {
                    color: #2980b9;
                    text-decoration: underline;
                }
                blockquote {
                    border-left: 4px solid #3498db;
                    padding-left: 1rem;
                    margin: 1.5rem 0;
                    color: #555;
                    font-style: italic;
                }
                ul, ol {
                    margin-bottom: 1.5rem;
                    padding-left: 2rem;
                }
                li {
                    margin-bottom: 0.5rem;
                }
                .mce-pagebreak { 
                    border-top: 2px dashed #999; 
                    margin: 2em 0; 
                    padding: 1em 0;
                    text-align: center;
                    color: #999;
                }
                .mce-pagebreak::before {
                    content: 'Page Break';
                }
            `,
            pagebreak_separator: '<!-- page -->',
            // Allow a comprehensive set of HTML elements and attributes
            extended_valid_elements: 'img[class|src|alt|title|width|height|loading|style],' +
                'a[href|target|rel|title|class|style],' +
                'iframe[src|width|height|frameborder|allowfullscreen|class|style],' +
                'video[src|controls|width|height|poster|preload|autoplay|muted|loop|class|style],' +
                'audio[src|controls|preload|autoplay|loop|class|style],' +
                'source[src|type],' +
                'figure[class|style],' +
                'figcaption[class|style],' +
                'mark[class|style],' +
                'small[class|style],' +
                'cite[class|style],' +
                'code[class|style],' +
                'pre[class|style],' +
                'blockquote[cite|class|style],' +
                'table[class|style|border|cellpadding|cellspacing],' +
                'thead[class|style],' +
                'tbody[class|style],' +
                'tfoot[class|style],' +
                'tr[class|style],' +
                'td[class|style|colspan|rowspan],' +
                'th[class|style|colspan|rowspan],' +
                'caption[class|style],' +
                'div[class|style|id],' +
                'span[class|style],' +
                'article[class|style],' +
                'section[class|style],' +
                'header[class|style],' +
                'footer[class|style],' +
                'aside[class|style],' +
                'nav[class|style]',
            // Keep the <!-- page --> comments
            valid_children: '+body[style]',
            custom_elements: '~comment',
            setup: function(editor) {
                editor.on('init', function() {
                    editor.addShortcut('ctrl+shift+p', 'Insert page break', function() {
                        editor.insertContent('<!-- page -->');
                    });
                });
            }
        });
        
        function showCreateForm() {
            document.getElementById('article-form').style.display = 'block';
            document.querySelector('[name="action"]').value = 'create';
        }
        
        // Auto-generate URL alias
        document.getElementById('title')?.addEventListener('blur', function() {
            if (!document.getElementById('slug').value) {
                const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                document.getElementById('slug').value = slug;
            }
        });
        
        // Document upload handler
        document.getElementById('document-upload')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const statusDiv = document.getElementById('upload-status');
            statusDiv.innerHTML = '<div class="alert alert-info">Converting document...</div>';
            
            const formData = new FormData();
            formData.append('document', file);
            
            fetch('/admin/api/convert-document.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Insert HTML into TinyMCE
                    if (tinymce.get('body')) {
                        tinymce.get('body').setContent(data.html);
                        statusDiv.innerHTML = '<div class="alert alert-success">Document imported successfully!</div>';
                    } else {
                        // Fallback for non-TinyMCE
                        document.getElementById('body').value = data.html;
                        statusDiv.innerHTML = '<div class="alert alert-success">Document imported successfully!</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-error">Error: ' + (data.error || 'Conversion failed') + '</div>';
                }
                // Clear the file input
                e.target.value = '';
            })
            .catch(error => {
                statusDiv.innerHTML = '<div class="alert alert-error">Error uploading document</div>';
                console.error('Upload error:', error);
            });
        });
    </script>
<?php require_once __DIR__ . '/templates/footer.php'; ?>