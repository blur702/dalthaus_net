<?php
/**
 * Site Settings Management
 * Allows configuration of site-wide settings including header customization
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Require admin authentication
Auth::requireAdmin();

$pdo = Database::getInstance();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid CSRF token';
        $messageType = 'error';
    } else {
        try {
            // Handle header image upload
            if (!empty($_FILES['header_image_upload']['name'])) {
                $uploadDir = '../uploads/headers/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileInfo = pathinfo($_FILES['header_image_upload']['name']);
                $extension = strtolower($fileInfo['extension']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($extension, $allowedExtensions)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
                }
                
                if ($_FILES['header_image_upload']['size'] > 5 * 1024 * 1024) { // 5MB limit
                    throw new Exception('File size exceeds 5MB limit.');
                }
                
                $newFilename = 'header_' . time() . '.' . $extension;
                $uploadPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['header_image_upload']['tmp_name'], $uploadPath)) {
                    $_POST['header_image'] = '/uploads/headers/' . $newFilename;
                    
                    // Delete old header image if exists
                    $oldImage = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'header_image'")->fetchColumn();
                    if ($oldImage && file_exists('..' . $oldImage)) {
                        unlink('..' . $oldImage);
                    }
                }
            }
            
            // Handle header image removal
            if (isset($_POST['remove_header_image']) && $_POST['remove_header_image'] === '1') {
                $oldImage = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'header_image'")->fetchColumn();
                if ($oldImage && file_exists('..' . $oldImage)) {
                    unlink('..' . $oldImage);
                }
                $_POST['header_image'] = '';
            }
            
            // Settings to update
            $settings = [
                'site_title' => $_POST['site_title'] ?? '',
                'site_motto' => $_POST['site_motto'] ?? '',
                'site_description' => $_POST['site_description'] ?? '',
                'site_keywords' => $_POST['site_keywords'] ?? '',
                'header_image' => $_POST['header_image'] ?? '',
                'header_height' => $_POST['header_height'] ?? '200',
                'header_overlay_color' => $_POST['header_overlay_color'] ?? 'rgba(0,0,0,0.3)',
                'header_position' => $_POST['header_position'] ?? 'center center',
                'header_text_color' => $_POST['header_text_color'] ?? '#ffffff',
                'footer_text' => $_POST['footer_text'] ?? '',
                'google_analytics' => $_POST['google_analytics'] ?? '',
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                'maintenance_message' => $_POST['maintenance_message'] ?? ''
            ];
            
            // Update settings in database
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            // Clear cache after settings update
            cacheClear();
            
            $message = 'Settings updated successfully!';
            $messageType = 'success';
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Load current settings
$settings = [];
$result = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $result->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Set defaults
$settings['site_title'] = $settings['site_title'] ?? 'Dalthaus.net';
$settings['header_height'] = $settings['header_height'] ?? '200';
$settings['header_overlay_color'] = $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)';
$settings['header_position'] = $settings['header_position'] ?? 'center center';
$settings['header_text_color'] = $settings['header_text_color'] ?? '#ffffff';
$settings['maintenance_mode'] = $settings['maintenance_mode'] ?? '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .settings-form {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .settings-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .settings-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="color"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #6c757d;
            font-size: 13px;
        }
        
        .color-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .color-input-wrapper input[type="color"] {
            width: 50px;
            height: 40px;
            padding: 2px;
            cursor: pointer;
        }
        
        .color-input-wrapper input[type="text"] {
            flex: 1;
        }
        
        .image-preview {
            margin-top: 10px;
            max-width: 400px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .image-preview img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .header-preview {
            margin-top: 20px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            position: relative;
        }
        
        .header-preview-content {
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .header-preview h1 {
            margin: 0;
            font-size: 2em;
        }
        
        .header-preview p {
            margin: 10px 0 0;
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .remove-image-btn {
            margin-top: 10px;
            padding: 8px 15px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .remove-image-btn:hover {
            background: #c82333;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .position-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 5px;
            max-width: 150px;
            margin-top: 10px;
        }
        
        .position-grid button {
            padding: 10px;
            border: 1px solid #ced4da;
            background: white;
            cursor: pointer;
            border-radius: 3px;
        }
        
        .position-grid button.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .position-grid button:hover {
            background: #e9ecef;
        }
        
        .position-grid button.active:hover {
            background: #2980b9;
        }
        
        .save-button {
            position: sticky;
            bottom: 20px;
            text-align: center;
            padding: 20px;
            background: linear-gradient(to top, rgba(248,249,250,1) 60%, rgba(248,249,250,0));
        }
        
        .save-button button {
            padding: 12px 40px;
            font-size: 16px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .save-button button:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h1>Admin Panel</h1>
            <ul>
                <li><a href="/admin/dashboard.php">Dashboard</a></li>
                <li><a href="/admin/articles.php">Articles</a></li>
                <li><a href="/admin/photobooks.php">Photobooks</a></li>
                <li><a href="/admin/menus.php">Menus</a></li>
                <li><a href="/admin/settings.php" class="active">Settings</a></li>
                <li><a href="/admin/logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-main">
            <h1>Site Settings</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <!-- General Settings -->
                <div class="settings-section">
                    <h2>General Settings</h2>
                    
                    <div class="form-group">
                        <label for="site_title">Site Title</label>
                        <input type="text" id="site_title" name="site_title" 
                               value="<?= htmlspecialchars($settings['site_title']) ?>" required>
                        <small>The main title of your website</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_motto">Site Motto / Tagline</label>
                        <input type="text" id="site_motto" name="site_motto" 
                               value="<?= htmlspecialchars($settings['site_motto'] ?? '') ?>">
                        <small>Optional tagline displayed below the site title</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Site Description</label>
                        <textarea id="site_description" name="site_description"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                        <small>SEO meta description for the homepage</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_keywords">Site Keywords</label>
                        <input type="text" id="site_keywords" name="site_keywords" 
                               value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>">
                        <small>Comma-separated keywords for SEO</small>
                    </div>
                </div>
                
                <!-- Header Settings -->
                <div class="settings-section">
                    <h2>Header Customization</h2>
                    
                    <div class="form-group">
                        <label for="header_image_upload">Header Background Image</label>
                        <input type="file" id="header_image_upload" name="header_image_upload" 
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <small>Upload a background image for the header (max 5MB)</small>
                        
                        <?php if (!empty($settings['header_image'])): ?>
                            <div class="image-preview">
                                <img src="<?= htmlspecialchars($settings['header_image']) ?>" alt="Header Image">
                            </div>
                            <button type="button" class="remove-image-btn" onclick="removeHeaderImage()">
                                Remove Image
                            </button>
                            <input type="hidden" name="remove_header_image" id="remove_header_image" value="0">
                            <input type="hidden" name="header_image" value="<?= htmlspecialchars($settings['header_image']) ?>">
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="header_height">Header Height (pixels)</label>
                        <input type="number" id="header_height" name="header_height" 
                               value="<?= htmlspecialchars($settings['header_height']) ?>" 
                               min="100" max="600" step="10">
                        <small>Height of the header area (100-600px)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="header_position">Background Position</label>
                        <select id="header_position" name="header_position">
                            <option value="left top" <?= $settings['header_position'] === 'left top' ? 'selected' : '' ?>>Top Left</option>
                            <option value="center top" <?= $settings['header_position'] === 'center top' ? 'selected' : '' ?>>Top Center</option>
                            <option value="right top" <?= $settings['header_position'] === 'right top' ? 'selected' : '' ?>>Top Right</option>
                            <option value="left center" <?= $settings['header_position'] === 'left center' ? 'selected' : '' ?>>Center Left</option>
                            <option value="center center" <?= $settings['header_position'] === 'center center' ? 'selected' : '' ?>>Center</option>
                            <option value="right center" <?= $settings['header_position'] === 'right center' ? 'selected' : '' ?>>Center Right</option>
                            <option value="left bottom" <?= $settings['header_position'] === 'left bottom' ? 'selected' : '' ?>>Bottom Left</option>
                            <option value="center bottom" <?= $settings['header_position'] === 'center bottom' ? 'selected' : '' ?>>Bottom Center</option>
                            <option value="right bottom" <?= $settings['header_position'] === 'right bottom' ? 'selected' : '' ?>>Bottom Right</option>
                        </select>
                        <small>Position of the background image</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="header_overlay_color">Overlay Color</label>
                        <div class="color-input-wrapper">
                            <input type="text" id="header_overlay_color" name="header_overlay_color" 
                                   value="<?= htmlspecialchars($settings['header_overlay_color']) ?>"
                                   placeholder="rgba(0,0,0,0.3)">
                        </div>
                        <small>Semi-transparent overlay color (use rgba format for transparency)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="header_text_color">Text Color</label>
                        <div class="color-input-wrapper">
                            <input type="color" id="header_text_color_picker" 
                                   value="<?= htmlspecialchars($settings['header_text_color']) ?>"
                                   onchange="document.getElementById('header_text_color').value = this.value">
                            <input type="text" id="header_text_color" name="header_text_color" 
                                   value="<?= htmlspecialchars($settings['header_text_color']) ?>">
                        </div>
                        <small>Color of the header text</small>
                    </div>
                    
                    <!-- Live Preview -->
                    <div class="form-group">
                        <label>Header Preview</label>
                        <div class="header-preview" id="header-preview">
                            <div class="header-preview-content">
                                <h1 id="preview-title"><?= htmlspecialchars($settings['site_title']) ?></h1>
                                <?php if (!empty($settings['site_motto'])): ?>
                                    <p id="preview-motto"><?= htmlspecialchars($settings['site_motto']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Settings -->
                <div class="settings-section">
                    <h2>Footer Settings</h2>
                    
                    <div class="form-group">
                        <label for="footer_text">Footer Text</label>
                        <textarea id="footer_text" name="footer_text"><?= htmlspecialchars($settings['footer_text'] ?? '') ?></textarea>
                        <small>Copyright text or footer content (HTML allowed)</small>
                    </div>
                </div>
                
                <!-- Advanced Settings -->
                <div class="settings-section">
                    <h2>Advanced Settings</h2>
                    
                    <div class="form-group">
                        <label for="google_analytics">Google Analytics ID</label>
                        <input type="text" id="google_analytics" name="google_analytics" 
                               value="<?= htmlspecialchars($settings['google_analytics'] ?? '') ?>"
                               placeholder="UA-XXXXX-Y or G-XXXXXX">
                        <small>Your Google Analytics tracking ID</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                   <?= $settings['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                            <label for="maintenance_mode">Enable Maintenance Mode</label>
                        </div>
                        <small>Show maintenance message to visitors (admins can still access)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenance_message">Maintenance Message</label>
                        <textarea id="maintenance_message" name="maintenance_message"><?= htmlspecialchars($settings['maintenance_message'] ?? 'We are currently performing maintenance. Please check back soon.') ?></textarea>
                        <small>Message displayed when maintenance mode is enabled</small>
                    </div>
                </div>
                
                <div class="save-button">
                    <button type="submit">Save Settings</button>
                </div>
            </form>
        </main>
    </div>
    
    <script>
        // Live preview updates
        document.getElementById('site_title').addEventListener('input', function() {
            document.getElementById('preview-title').textContent = this.value || 'Site Title';
        });
        
        document.getElementById('site_motto').addEventListener('input', function() {
            let mottoEl = document.getElementById('preview-motto');
            if (!mottoEl && this.value) {
                mottoEl = document.createElement('p');
                mottoEl.id = 'preview-motto';
                document.querySelector('.header-preview-content').appendChild(mottoEl);
            }
            if (mottoEl) {
                if (this.value) {
                    mottoEl.textContent = this.value;
                    mottoEl.style.display = 'block';
                } else {
                    mottoEl.style.display = 'none';
                }
            }
        });
        
        document.getElementById('header_height').addEventListener('input', function() {
            document.getElementById('header-preview').style.height = this.value + 'px';
        });
        
        document.getElementById('header_overlay_color').addEventListener('input', function() {
            updateHeaderPreview();
        });
        
        document.getElementById('header_position').addEventListener('change', function() {
            updateHeaderPreview();
        });
        
        document.getElementById('header_text_color').addEventListener('input', function() {
            document.querySelector('.header-preview-content').style.color = this.value;
            document.getElementById('header_text_color_picker').value = this.value;
        });
        
        document.getElementById('header_text_color_picker').addEventListener('change', function() {
            document.getElementById('header_text_color').value = this.value;
            document.querySelector('.header-preview-content').style.color = this.value;
        });
        
        function updateHeaderPreview() {
            const preview = document.getElementById('header-preview');
            const overlayColor = document.getElementById('header_overlay_color').value;
            const position = document.getElementById('header_position').value;
            const height = document.getElementById('header_height').value;
            const textColor = document.getElementById('header_text_color').value;
            
            <?php if (!empty($settings['header_image'])): ?>
            const imageUrl = '<?= htmlspecialchars($settings['header_image']) ?>';
            preview.style.backgroundImage = `linear-gradient(${overlayColor}, ${overlayColor}), url('${imageUrl}')`;
            preview.style.backgroundSize = 'cover';
            preview.style.backgroundPosition = position;
            <?php else: ?>
            preview.style.background = overlayColor;
            <?php endif; ?>
            
            preview.style.height = height + 'px';
            document.querySelector('.header-preview-content').style.color = textColor;
        }
        
        function removeHeaderImage() {
            if (confirm('Are you sure you want to remove the header image?')) {
                document.getElementById('remove_header_image').value = '1';
                document.querySelector('.image-preview').style.display = 'none';
                document.querySelector('.remove-image-btn').style.display = 'none';
                updateHeaderPreview();
            }
        }
        
        // Initialize preview
        updateHeaderPreview();
    </script>
</body>
</html>