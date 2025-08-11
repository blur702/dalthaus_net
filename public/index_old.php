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

// Get published articles
$articles = $pdo->query("
    SELECT * FROM content 
    WHERE type = 'article' 
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Get published photobooks
$photobooks = $pdo->query("
    SELECT * FROM content 
    WHERE type = 'photobook' 
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Get top menu items
$topMenu = $pdo->query("
    SELECT c.* FROM menus m
    JOIN content c ON m.content_id = c.id
    WHERE m.location = 'top' 
    AND m.is_active = TRUE
    AND c.deleted_at IS NULL
    ORDER BY m.sort_order
")->fetchAll();

// Get bottom menu items
$bottomMenu = $pdo->query("
    SELECT c.* FROM menus m
    JOIN content c ON m.content_id = c.id
    WHERE m.location = 'bottom' 
    AND m.is_active = TRUE
    AND c.deleted_at IS NULL
    ORDER BY m.sort_order
")->fetchAll();

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dalthaus.net</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css">
</head>
<body>
    <div class="page-wrapper">
        <!-- Header with site title and top menu -->
        <header class="site-header">
            <div class="header-content">
                <h1 class="site-title">Dalthaus.net</h1>
                
                <?php if ($topMenu && count($topMenu) > 0): ?>
                <nav class="top-menu" role="navigation" aria-label="Main navigation">
                    <ul>
                        <?php foreach ($topMenu as $item): ?>
                        <li><a href="/<?= $item['type'] ?>/<?= htmlspecialchars($item['slug']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Main content area -->
        <main class="main-content" role="main">
            <div class="content-layout">
                <!-- Left column: Articles -->
                <section class="articles-section">
                    <h2 class="section-title">Articles</h2>
                    <div class="articles-list">
                        <?php if ($articles && count($articles) > 0): ?>
                            <?php foreach ($articles as $article): ?>
                            <article class="article-item">
                                <div class="article-image">
                                    <?php
                                    // Extract first image from content or show placeholder
                                    $hasImage = preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $article['body'], $imgMatch);
                                    if ($hasImage && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgMatch[1])) {
                                        echo '<img src="' . htmlspecialchars($imgMatch[1]) . '" alt="" class="article-thumbnail">';
                                    } else {
                                        echo '<div class="image-placeholder article-thumbnail"></div>';
                                    }
                                    ?>
                                </div>
                                <div class="article-content">
                                    <h3 class="article-title">
                                        <a href="/article/<?= htmlspecialchars($article['slug']) ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="article-details">
                                        <div class="detail-line">
                                            <span class="label">Written by</span>
                                            <span class="value"><?= htmlspecialchars($article['author'] ?? 'Don Althaus') ?></span>
                                        </div>
                                        <div class="detail-line">
                                            <span class="label">Category:</span>
                                            <span class="value">Articles</span>
                                        </div>
                                        <div class="detail-line">
                                            <span class="label">Published:</span>
                                            <span class="value">
                                                <time datetime="<?= $article['published_at'] ?? $article['created_at'] ?>">
                                                    <?= date('d F Y', strtotime($article['published_at'] ?? $article['created_at'])) ?>
                                                </time>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="article-excerpt">
                                        <?php
                                        $excerpt = strip_tags($article['body']);
                                        echo htmlspecialchars(mb_substr($excerpt, 0, 200)) . '...';
                                        ?>
                                    </div>
                                    <a href="/article/<?= htmlspecialchars($article['slug']) ?>" class="read-more">Read more →</a>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-content">No articles published yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Right column: Photobooks -->
                <aside class="photobooks-section">
                    <h2 class="section-title">Photo Books</h2>
                    <div class="photobooks-list">
                        <?php if ($photobooks && count($photobooks) > 0): ?>
                            <?php foreach ($photobooks as $book): ?>
                            <div class="photobook-item">
                                <div class="photobook-image">
                                    <?php
                                    // Extract first image from content or show placeholder
                                    $hasImage = preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $book['body'], $imgMatch);
                                    if ($hasImage && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgMatch[1])) {
                                        echo '<img src="' . htmlspecialchars($imgMatch[1]) . '" alt="" class="photobook-thumbnail">';
                                    } else {
                                        echo '<div class="image-placeholder photobook-thumbnail"></div>';
                                    }
                                    ?>
                                </div>
                                <div class="photobook-content">
                                    <h3 class="photobook-title">
                                        <a href="/photobook/<?= htmlspecialchars($book['slug']) ?>">
                                            <?= htmlspecialchars($book['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="photobook-details">
                                        <div class="detail-line">
                                            <span class="label">Written by</span>
                                            <span class="value"><?= htmlspecialchars($book['author'] ?? 'Don Althaus') ?></span>
                                        </div>
                                        <div class="detail-line">
                                            <span class="label">Category:</span>
                                            <span class="value">Photo Books</span>
                                        </div>
                                        <div class="detail-line">
                                            <span class="label">Published:</span>
                                            <span class="value">
                                                <time datetime="<?= $book['published_at'] ?? $book['created_at'] ?>">
                                                    <?= date('d F Y', strtotime($book['published_at'] ?? $book['created_at'])) ?>
                                                </time>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="photobook-preview">
                                        <?php
                                        // Get first bit of text as preview
                                        $preview = strip_tags($book['body']);
                                        echo htmlspecialchars(mb_substr($preview, 0, 120)) . '...';
                                        ?>
                                    </div>
                                    <a href="/photobook/<?= htmlspecialchars($book['slug']) ?>" class="view-book">Read →</a>
                                </div>
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
                    <li><a href="/<?= $item['type'] ?>/<?= htmlspecialchars($item['slug']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="footer-info">
                <p>&copy; <?= date('Y') ?> Dalthaus.net. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();
cacheSet($cacheKey, $html);
echo $html;