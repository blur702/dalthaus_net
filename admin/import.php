<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireAdmin();
$message = '';
$error = '';
$convertedHtml = '';

// Handle document import
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } elseif (isset($_FILES['document'])) {
        $file = $_FILES['document'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload failed';
        } else {
            // Save to temp directory
            $tempFile = TEMP_PATH . '/' . uniqid() . '_' . basename($file['name']);
            
            if (move_uploaded_file($file['tmp_name'], $tempFile)) {
                // Run Python converter
                $command = escapeshellcmd(PYTHON_PATH . ' ' . CONVERTER_SCRIPT . ' ' . escapeshellarg($tempFile));
                $output = shell_exec($command . ' 2>&1');
                
                $result = json_decode($output, true);
                
                if ($result && $result['success']) {
                    $convertedHtml = $result['html'];
                    $message = 'Document converted successfully';
                } else {
                    $error = 'Conversion failed: ' . ($result['error'] ?? 'Unknown error');
                }
                
                // Clean up temp file
                unlink($tempFile);
            } else {
                $error = 'Failed to save uploaded file';
            }
        }
    }
}

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Documents</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <nav class="admin-nav">
            <h1>CMS Admin</h1>
            <ul>
                <li><a href="/admin">Dashboard</a></li>
                <li><a href="/admin/articles">Articles</a></li>
                <li><a href="/admin/photobooks">Photobooks</a></li>
                <li><a href="/admin/menus">Menus</a></li>
                <li><a href="/admin/sort">Sort Content</a></li>
                <li><a href="/admin/upload">Upload Files</a></li>
                <li class="active"><a href="/admin/import">Import Documents</a></li>
                <li><a href="/admin/logout">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-content">
            <h2>Import Documents</h2>
            <p>Upload a Word document or PDF to convert it to HTML for use in articles.</p>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="import-form">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    
                    <div class="form-group">
                        <label for="document">Select Document</label>
                        <input type="file" id="document" name="document" accept=".doc,.docx,.pdf" required>
                        <small>Supported formats: Word (.doc, .docx), PDF (.pdf)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Convert Document</button>
                </form>
            </div>
            
            <?php if ($convertedHtml): ?>
            <div class="converted-content">
                <h3>Converted Content</h3>
                <p>You can copy this HTML into a new article or edit it below:</p>
                
                <div class="form-group">
                    <label for="converted-html">HTML Content</label>
                    <textarea id="converted-html" rows="20"><?= htmlspecialchars($convertedHtml) ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button onclick="copyToClipboard()" class="btn btn-secondary">Copy HTML</button>
                    <button onclick="createArticle()" class="btn btn-primary">Create New Article</button>
                </div>
                
                <h4>Preview</h4>
                <div class="preview-box" style="border: 1px solid #ddd; padding: 20px; background: white; margin-top: 20px;">
                    <?= $convertedHtml ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        tinymce.init({
            selector: '#converted-html',
            height: 400,
            menubar: true,
            license_key: 'gpl',
            plugins: 'link image lists code pagebreak preview',
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
        
        function copyToClipboard() {
            const html = tinymce.get('converted-html').getContent();
            navigator.clipboard.writeText(html).then(() => {
                alert('HTML copied to clipboard');
            });
        }
        
        function createArticle() {
            const html = tinymce.get('converted-html').getContent();
            // Store in session and redirect to article creation
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/admin/articles';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'imported_content';
            input.value = html;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>