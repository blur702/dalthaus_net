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

// Set page title
$pageTitle = 'Articles';

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
        
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
                                <div class="article-meta">
                                    Don Althaus · Articles · <?= date('d F Y', strtotime($article['published_date'] ?? $article['created_at'])) ?>
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
        
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';