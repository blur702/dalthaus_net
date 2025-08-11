<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$alias = $_GET['params'][0] ?? '';

if (!$alias) {
    showError(404);
}

$pdo = Database::getInstance();

// Try cache first
$cacheKey = 'article_' . $alias;
$cached = cacheGet($cacheKey);

if ($cached) {
    echo $cached;
    exit;
}

// Get article from new articles table
$stmt = $pdo->prepare("
    SELECT * FROM articles 
    WHERE alias = ? 
    AND status = 'published' 
    AND deleted_at IS NULL
");
$stmt->execute([$alias]);
$article = $stmt->fetch();

if (!$article) {
    showError(404);
}

// Get attachments
$stmt = $pdo->prepare("
    SELECT * FROM content_attachments 
    WHERE content_type = 'article' 
    AND content_id = ?
");
$stmt->execute([$article['id']]);
$attachments = $stmt->fetchAll();

// Set page title
$pageTitle = $article['title'];

ob_start();

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
    
    <main id="main" class="site-main">
        <div class="container">
            <article class="article-content">
                <header class="article-header">
                    <h1><?= htmlspecialchars($article['title']) ?></h1>
                    <div class="article-meta">
                        Published on <?= date('F j, Y', strtotime($article['published_date'] ?? $article['created_at'])) ?>
                        <?php if ($article['updated_at'] > $article['created_at']): ?>
                        • Updated <?= date('F j, Y', strtotime($article['updated_at'])) ?>
                        <?php endif; ?>
                    </div>
                </header>
                
                <div class="article-body">
                    <?= processContentImages($article['content'] ?? '') ?>
                </div>
                
                <?php if ($attachments && count($attachments) > 0): ?>
                <div class="article-attachments">
                    <h3>Attachments</h3>
                    <ul>
                        <?php foreach ($attachments as $attachment): ?>
                        <li>
                            <a href="/download/<?= htmlspecialchars($attachment['filename']) ?>">
                                <?= htmlspecialchars($attachment['document_name']) ?>
                            </a>
                            (<?= number_format($attachment['file_size'] / 1024, 1) ?> KB)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </article>
            
            <nav class="article-nav">
                <a href="/articles" class="back-link">← Back to Articles</a>
            </nav>
        </div>
    </main>
    
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';
$html = ob_get_clean();
cacheSet($cacheKey, $html);
echo $html;