<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getInstance();

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

// Get top menu items from new menu_items table
$topMenu = $pdo->query("
    SELECT * FROM menu_items
    WHERE menu_location = 'top' 
    AND is_active = TRUE
    ORDER BY sort_order
")->fetchAll();

// Get bottom menu items from new menu_items table
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

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_title'] ?? 'Dalthaus.net') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css">
    <style>
        /* Update layout to 66/33 */
        .content-layout {
            grid-template-columns: 2fr 1fr;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Header with site title and top menu -->
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
                
                <!-- Hamburger menu button (always visible) -->
                <button class="hamburger-menu" id="hamburger-menu" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
        
        <!-- Slide-out navigation menu -->
        <nav class="slide-menu" id="slide-menu">
            <ul>
                <?php foreach ($topMenu as $item): ?>
                <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
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
                                    <div class="article-details">
                                        <div class="detail-line">
                                            <span class="label">Written by</span>
                                            <span class="value">Don Althaus</span>
                                        </div>
                                        <div class="detail-line">
                                            <span class="label">Category:</span>
                                            <span class="value">Articles</span>
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
                                <div class="photobook-details">
                                    <div class="detail-line">
                                        <span class="label">Written by</span>
                                        <span class="value">Don Althaus</span>
                                    </div>
                                    <div class="detail-line">
                                        <span class="label">Category:</span>
                                        <span class="value">Photo Books</span>
                                    </div>
                                    <div class="detail-line">
                                        <span class="label">Published:</span>
                                        <span class="value">
                                            <time datetime="<?= $book['published_date'] ?? $book['created_at'] ?>">
                                                <?= date('d F Y', strtotime($book['published_date'] ?? $book['created_at'])) ?>
                                            </time>
                                        </span>
                                    </div>
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
        
        <!-- Footer with bottom menu -->
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
    
    <!-- Hamburger menu script -->
    <script>
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            const menu = document.getElementById('slide-menu');
            const hamburger = this;
            menu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
        
        // Close menu when clicking outside
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
<?php
$html = ob_get_clean();
cacheSet($cacheKey, $html);
echo $html;