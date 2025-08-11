<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getInstance();

// Get all published photobooks
$photobooks = $pdo->query("
    SELECT * FROM photobooks 
    WHERE status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC
")->fetchAll();

// Set page title
$pageTitle = 'Photo Books';

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
        
        <main class="main-content" role="main">
            <div class="single-column-layout">
                <h1>Photo Books</h1>
                <div class="photobooks-list">
                    <?php if ($photobooks && count($photobooks) > 0): ?>
                        <?php foreach ($photobooks as $book): ?>
                        <article class="photobook-list-item">
                            <div class="photobook-list-image">
                                <?php
                                $imgSrc = $book['teaser_image'] ?? $book['featured_image'];
                                if (!$imgSrc && !empty($book['body'])) {
                                    preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $book['body'], $imgMatch);
                                    $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                }
                                $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                
                                if ($hasImage) {
                                    echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="" class="photobook-list-thumbnail">';
                                } else {
                                    echo '<div class="image-placeholder photobook-list-thumbnail"></div>';
                                }
                                ?>
                            </div>
                            <div class="photobook-list-content">
                                <h2 class="photobook-list-title">
                                    <a href="/photobook/<?= htmlspecialchars($book['alias']) ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </h2>
                                <div class="photobook-meta">
                                    Don Althaus · Photo Books · <?= date('d F Y', strtotime($book['published_date'] ?? $book['created_at'])) ?>
                                </div>
                                <div class="photobook-list-excerpt">
                                    <?= htmlspecialchars($book['summary'] ?? mb_substr(strip_tags($book['body'] ?? ''), 0, 300) . '...') ?>
                                </div>
                                <a href="/photobook/<?= htmlspecialchars($book['alias']) ?>" class="read-more">Read more →</a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-content">No photo books published yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';