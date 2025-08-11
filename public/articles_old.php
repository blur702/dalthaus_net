<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getInstance();

// Get all published articles
$articles = $pdo->query("
    SELECT * FROM articles 
    WHERE status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC
")->fetchAll();

// Get menu items
$topMenu = $pdo->query("
    SELECT * FROM menu_items
    WHERE menu_location = 'top' 
    AND is_active = TRUE
    ORDER BY sort_order
")->fetchAll();

$bottomMenu = $pdo->query("
    SELECT * FROM menu_items
    WHERE menu_location = 'bottom' 
    AND is_active = TRUE
    ORDER BY sort_order
")->fetchAll();

// Get site settings
$settings = [];
$result = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - <?= htmlspecialchars($settings['site_title'] ?? 'Dalthaus.net') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css">
</head>
<body>
    <div class="page-wrapper">
        <header class="site-header" <?php if (!empty($settings['header_image'])): ?>
            style="background-image: linear-gradient(<?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>, <?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>), url('<?= htmlspecialchars($settings['header_image']) ?>'); background-size: cover; background-position: center; height: <?= $settings['header_height'] ?? '200' ?>px;"
        <?php endif; ?>>
            <div class="header-content">
                <div class="header-text">
                    <h1 class="site-title">
                        <a href="/" style="color: white; text-decoration: none;">
                            <?= $settings['site_title'] ?? 'Dalthaus.net' ?>
                        </a>
                    </h1>
                    <?php if (!empty($settings['site_motto'])): ?>
                    <p class="site-motto"><?= $settings['site_motto'] ?></p>
                    <?php endif; ?>
                </div>
                
                <button class="hamburger-menu" id="hamburger-menu" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
        
        <nav class="slide-menu" id="slide-menu">
            <ul>
                <?php foreach ($topMenu as $item): ?>
                <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <main class="main-content" role="main">
            <div class="single-column-layout">
                <h1>Articles</h1>
                <div class="articles-list">
                    <?php if ($articles && count($articles) > 0): ?>
                        <?php foreach ($articles as $article): ?>
                        <article class="article-item">
                            <div class="article-image">
                                <?php
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
                                <h2 class="article-title">
                                    <a href="/article/<?= htmlspecialchars($article['alias']) ?>">
                                        <?= htmlspecialchars($article['title']) ?>
                                    </a>
                                </h2>
                                <div class="article-details">
                                    <div class="detail-line">
                                        <span class="label">Written by</span>
                                        <span class="value">Don Althaus</span>
                                    </div>
                                    <div class="detail-line">
                                        <span class="label">Published:</span>
                                        <span class="value">
                                            <time datetime="<?= $article['published_date'] ?? $article['created_at'] ?>">
                                                <?= date('d F Y', strtotime($article['published_date'] ?? $article['created_at'])) ?>
                                            </time>
                                        </span>
                                    </div>
                                </div>
                                <div class="article-excerpt">
                                    <?= htmlspecialchars($article['excerpt'] ?? mb_substr(strip_tags($article['content'] ?? ''), 0, 300) . '...') ?>
                                </div>
                                <a href="/article/<?= htmlspecialchars($article['alias']) ?>" class="read-more">Read more →</a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-content">No articles published yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <footer class="site-footer">
            <?php if ($bottomMenu && count($bottomMenu) > 0): ?>
            <nav class="bottom-menu" role="navigation" aria-label="Footer navigation">
                <ul>
                    <?php foreach ($bottomMenu as $item): ?>
                    <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="footer-info">
                <p><?= $settings['copyright_notice'] ?? '© ' . date('Y') . ' Dalthaus.net. All rights reserved.' ?></p>
            </div>
        </footer>
    </div>
    
    <script>
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            const menu = document.getElementById('slide-menu');
            const hamburger = this;
            menu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
        
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('slide-menu');
            const hamburger = document.getElementById('hamburger-menu');
            if (!menu.contains(event.target) && !hamburger.contains(event.target)) {
                menu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    </script>
</body>
</html>