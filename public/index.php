<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = Database::getInstance();

// Check maintenance mode
$settings = [];
$result = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// If maintenance mode is enabled and user is not admin, show maintenance page
if (!empty($settings['maintenance_mode']) && $settings['maintenance_mode'] === '1') {
    if (!Auth::isLoggedIn()) {
        $maintenanceMessage = $settings['maintenance_message'] ?? 'We are currently performing maintenance. Please check back soon.';
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance - <?= htmlspecialchars($settings['site_title'] ?? 'Dalthaus.net') ?></title>
            <style>
                body {
                    font-family: 'Gelasio', serif;
                    background: #f8f9fa;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    padding: 20px;
                }
                .maintenance-box {
                    background: white;
                    border-radius: 8px;
                    padding: 40px;
                    max-width: 500px;
                    text-align: center;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #2c3e50;
                    margin-bottom: 20px;
                }
                p {
                    color: #6c757d;
                    line-height: 1.6;
                }
            </style>
        </head>
        <body>
            <div class="maintenance-box">
                <h1>Under Maintenance</h1>
                <p><?= nl2br(htmlspecialchars($maintenanceMessage)) ?></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Get cached version if available
$cacheKey = 'homepage';
$cached = cacheGet($cacheKey);

if ($cached) {
    echo $cached;
    exit;
}

// Get published articles from new articles table
$articles = $pdo->query("
    SELECT * FROM articles 
    WHERE status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Get published photobooks from new photobooks table
$photobooks = $pdo->query("
    SELECT * FROM photobooks 
    WHERE status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Set page title (optional - will use site title by default)
// $pageTitle = 'Home';

ob_start();

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
        
        <!-- Main content area -->
        <main class="main-content" role="main">
            <div class="content-layout">
                <!-- Left column: Articles (66%) -->
                <section class="articles-section">
                    <h2 class="section-title">Articles</h2>
                    <div class="articles-list">
                        <?php if ($articles && count($articles) > 0): ?>
                            <?php foreach ($articles as $article): ?>
                            <article class="article-item">
                                <div class="article-image">
                                    <?php
                                    // Use featured image or extract from content or show placeholder
                                    $imgSrc = $article['featured_image'];
                                    if (!$imgSrc && !empty($article['content'])) {
                                        preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $article['content'], $imgMatch);
                                        $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                    }
                                    $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                    
                                    if ($hasImage) {
                                        echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="" class="article-thumbnail">';
                                    } else {
                                        echo '<div class="image-placeholder article-thumbnail"></div>';
                                    }
                                    ?>
                                </div>
                                <div class="article-content">
                                    <h3 class="article-title">
                                        <a href="/article/<?= htmlspecialchars($article['alias']) ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="article-meta">
                                        Don Althaus · Articles · <?= date('d F Y', strtotime($article['published_date'] ?? $article['created_at'])) ?>
                                    </div>
                                    <div class="article-excerpt">
                                        <?= htmlspecialchars($article['excerpt'] ?? mb_substr(strip_tags($article['content'] ?? ''), 0, 200) . '...') ?>
                                    </div>
                                    <a href="/article/<?= htmlspecialchars($article['alias']) ?>" class="read-more">Read more →</a>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-content">No articles published yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Right column: Photobooks (33%) -->
                <aside class="photobooks-section">
                    <h2 class="section-title">Photo Books</h2>
                    <div class="photobooks-list">
                        <?php if ($photobooks && count($photobooks) > 0): ?>
                            <?php foreach ($photobooks as $book): ?>
                            <div class="photobook-item">
                                <?php
                                // Use teaser image, featured image, or extract from content
                                $imgSrc = $book['teaser_image'] ?? $book['featured_image'];
                                if (!$imgSrc && !empty($book['body'])) {
                                    preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $book['body'], $imgMatch);
                                    $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                }
                                $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                
                                if ($hasImage) {
                                    echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="" class="photobook-thumbnail">';
                                } else {
                                    echo '<div class="image-placeholder photobook-thumbnail"></div>';
                                }
                                ?>
                                <h3 class="photobook-title">
                                    <a href="/photobook/<?= htmlspecialchars($book['alias']) ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </h3>
                                <div class="photobook-meta">
                                    Don Althaus · Photo Books · <?= date('d F Y', strtotime($book['published_date'] ?? $book['created_at'])) ?>
                                </div>
                                <div class="photobook-preview">
                                    <?= htmlspecialchars($book['summary'] ?? mb_substr(strip_tags($book['body'] ?? ''), 0, 120) . '...') ?>
                                </div>
                                <a href="/photobook/<?= htmlspecialchars($book['alias']) ?>" class="view-book">Read →</a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-content">No photo books published yet.</p>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </main>
        
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';

$html = ob_get_clean();
cacheSet($cacheKey, $html);
echo $html;