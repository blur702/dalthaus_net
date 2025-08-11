<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/page_tracker.php';

$alias = $_GET['params'][0] ?? '';

if (!$alias) {
    showError(404);
}

$pdo = Database::getInstance();

// Get article from articles table
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

// Get stored page information from articles table
$pageInfo = getPageInfo($pdo, $article['id'], 'articles');

// Parse article content into pages
$pages = explode('<!-- page -->', $article['content'] ?? '');
$totalPages = count($pages);

// Clean up pages - remove empty ones
$pages = array_filter($pages, function($page) {
    return !empty(trim($page));
});
$pages = array_values($pages); // Re-index
$totalPages = count($pages) ?: 1;

// If no page breaks, treat entire content as single page
if ($totalPages === 0) {
    $pages = [$article['content'] ?? ''];
    $totalPages = 1;
}

// If we don't have stored page info, generate it
if (!$pageInfo) {
    $pageData = extractPageInfo($article['content']);
    $pageInfo = $pageData;
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

// Additional styles for multi-page articles
$additionalStyles = '
<style>
    .article-content {
        transition: opacity 0.3s ease;
    }
    
    .page-selector {
        padding: 0.4rem 0.6rem;
        font-size: 0.95rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: white;
        color: #333;
        cursor: pointer;
        max-width: 300px;
        margin: 0 0.5rem;
    }
    
    .page-selector:hover {
        border-color: #3498db;
    }
    
    .page-selector:focus {
        outline: 2px solid #3498db;
        outline-offset: 2px;
    }
    
    .page-count {
        font-size: 0.95rem;
        color: #666;
    }
    
    .article-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid #ddd;
        margin-bottom: 2rem;
    }
    
    .article-controls.bottom-controls {
        border-bottom: none;
        border-top: 1px solid #ddd;
        margin-top: 2rem;
        padding-top: 2rem;
    }
    
    .nav-button {
        padding: 0.5rem 1rem;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-family: \'Arimo\', sans-serif;
        transition: background 0.3s;
    }
    
    .nav-button:hover:not(:disabled) {
        background: #2980b9;
    }
    
    .nav-button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .page-indicator {
        display: flex;
        align-items: center;
    }
    
    .page-dots {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .page-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #ddd;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .page-dot:hover {
        background: #999;
    }
    
    .page-dot.active {
        background: #3498db;
        transform: scale(1.2);
    }
    
    @media print {
        .article-controls,
        .site-header,
        .site-footer,
        .article-nav {
            display: none;
        }
    }
</style>
';

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
    
    <main id="main" class="site-main">
        <div class="container">
            <article class="article-wrapper">
                <header class="article-header">
                    <h1><?= htmlspecialchars($article['title']) ?></h1>
                    <div class="article-meta">
                        Published on <?= date('F j, Y', strtotime($article['published_date'] ?? $article['created_at'])) ?>
                        <?php if ($article['updated_at'] > $article['created_at']): ?>
                        • Updated <?= date('F j, Y', strtotime($article['updated_at'])) ?>
                        <?php endif; ?>
                    </div>
                </header>
                
                <?php if ($totalPages > 1): ?>
                <div class="article-controls">
                    <button class="nav-button" id="prev-btn" onclick="navigatePage(-1)" aria-label="Previous page">
                        <span class="arrow">←</span> Previous
                    </button>
                    <div class="page-indicator">
                        <select id="page-selector" onchange="goToPage(this.value)" class="page-selector">
                            <?php if ($pageInfo && isset($pageInfo['pages'])): ?>
                                <?php foreach ($pageInfo['pages'] as $page): ?>
                                    <option value="<?= $page['page'] ?>">
                                        <?= $page['page'] ?>. <?= htmlspecialchars($page['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                    <option value="<?= $i ?>">Page <?= $i ?></option>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </select>
                        <span class="page-count">of <?= $totalPages ?></span>
                    </div>
                    <button class="nav-button" id="next-btn" onclick="navigatePage(1)" aria-label="Next page">
                        Next <span class="arrow">→</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <div id="article-content" class="article-body article-content">
                    <!-- Article pages will be loaded here -->
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="article-controls bottom-controls">
                    <button class="nav-button" onclick="navigatePage(-1)" aria-label="Previous page">
                        <span class="arrow">←</span> Previous
                    </button>
                    <div class="page-dots">
                        <?php for($i = 1; $i <= min($totalPages, 10); $i++): ?>
                        <span class="page-dot" data-page="<?= $i ?>" onclick="goToPage(<?= $i ?>)"></span>
                        <?php endfor; ?>
                        <?php if ($totalPages > 10): ?>
                        <span class="page-more">...</span>
                        <?php endif; ?>
                    </div>
                    <button class="nav-button" onclick="navigatePage(1)" aria-label="Next page">
                        Next <span class="arrow">→</span>
                    </button>
                </div>
                <?php endif; ?>
                
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
// Additional scripts for article navigation
$additionalScripts = '
<script>
    // Store pages data with processed images
    const pages = ' . json_encode(array_map('processContentImages', $pages)) . ';
    const slug = ' . json_encode($alias) . ';
    const totalPages = ' . $totalPages . ';
    let currentPage = 1;
        
    // Initialize
    document.addEventListener("DOMContentLoaded", function() {
        // Check if there\'s a page in the URL hash
        const hash = window.location.hash;
        if (hash) {
            const pageNum = parseInt(hash.replace("#page-", ""));
            if (pageNum && pageNum > 0 && pageNum <= totalPages) {
                currentPage = pageNum;
            }
        }
        
        loadPage(currentPage);
        updateHistory();
    });
    
    // Handle browser back/forward
    window.addEventListener("popstate", function(event) {
        if (event.state && event.state.page) {
            currentPage = event.state.page;
            loadPage(currentPage);
        }
    });
    
    function navigatePage(direction) {
        const newPage = currentPage + direction;
        if (newPage >= 1 && newPage <= totalPages) {
            currentPage = newPage;
            loadPage(currentPage);
            updateHistory();
            
            // Scroll to top of article
            document.querySelector(".article-wrapper").scrollIntoView({ behavior: "smooth" });
        }
    }
    
    function goToPage(page) {
        page = parseInt(page);
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            loadPage(currentPage);
            updateHistory();
            
            // Scroll to top of article
            document.querySelector(".article-wrapper").scrollIntoView({ behavior: "smooth" });
        }
    }
    
    function loadPage(pageNum) {
        const content = document.getElementById("article-content");
        const pageSelector = document.getElementById("page-selector");
        
        // Fade out current content
        content.style.opacity = "0";
        
        setTimeout(() => {
            // Update content
            content.innerHTML = pages[pageNum - 1] || "";
            
            // Update page selector
            if (pageSelector) {
                pageSelector.value = pageNum;
            }
            
            // Update navigation buttons
            updateNavButtons();
            
            // Update page dots
            updatePageDots();
            
            // Fade in new content
            content.style.opacity = "1";
        }, 200);
    }
    
    function updateNavButtons() {
        const prevButtons = document.querySelectorAll(".nav-button");
        prevButtons.forEach(button => {
            if (button.textContent.includes("Previous")) {
                button.disabled = currentPage === 1;
            } else if (button.textContent.includes("Next")) {
                button.disabled = currentPage === totalPages;
            }
        });
    }
    
    function updatePageDots() {
        const dots = document.querySelectorAll(".page-dot");
        dots.forEach((dot, index) => {
            dot.classList.toggle("active", index + 1 === currentPage);
        });
    }
    
    function updateHistory() {
        const url = `/article/${slug}#page-${currentPage}`;
        const state = { page: currentPage };
        
        if (window.location.hash === `#page-${currentPage}`) {
            return; // Already on this page
        }
        
        history.pushState(state, "", url);
    }
</script>
';

// Include footer template
require_once __DIR__ . '/../includes/footer.php';