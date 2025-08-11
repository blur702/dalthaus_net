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
                <div class="article-top-nav">
                    <select id="page-selector" onchange="goToPage(this.value)" class="page-selector-elegant">
                        <?php if ($pageInfo && isset($pageInfo['pages'])): ?>
                            <?php foreach ($pageInfo['pages'] as $page): ?>
                                <option value="<?= $page['page'] ?>">
                                    <?= $page['page'] ?>. <?= htmlspecialchars($page['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                <option value="<?= $i ?>">Page <?= $i ?> of <?= $totalPages ?></option>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div id="article-content" class="article-body article-content">
                    <!-- Article pages will be loaded here -->
                </div>
                
                <?php if ($totalPages > 1): ?>
                <nav class="page-navigation bottom-nav" aria-label="Page navigation">
                    <button class="page-nav-arrow" id="prev-btn-bottom" onclick="navigatePage(-1)" aria-label="Previous page">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    
                    <div class="page-numbers">
                        <?php 
                        $maxVisible = 7;
                        
                        if ($totalPages <= $maxVisible): 
                            for($i = 1; $i <= $totalPages; $i++): ?>
                                <button class="page-number <?= $i === 1 ? 'active' : '' ?>" 
                                        onclick="goToPage(<?= $i ?>)" 
                                        data-page="<?= $i ?>">
                                    <?= $i ?>
                                </button>
                            <?php endfor;
                        else: ?>
                            <button class="page-number active" onclick="goToPage(1)" data-page="1">1</button>
                            <button class="page-number" onclick="goToPage(2)" data-page="2">2</button>
                            <button class="page-number" onclick="goToPage(3)" data-page="3">3</button>
                            <span class="page-ellipsis">...</span>
                            <button class="page-number" onclick="goToPage(<?= $totalPages ?>)" data-page="<?= $totalPages ?>"><?= $totalPages ?></button>
                        <?php endif; ?>
                    </div>
                    
                    <button class="page-nav-arrow" id="next-btn-bottom" onclick="navigatePage(1)" aria-label="Next page">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                </nav>
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
        
        // Fade out current content
        content.style.opacity = "0";
        
        setTimeout(() => {
            // Update content
            content.innerHTML = pages[pageNum - 1] || "";
            
            // Update dropdown selector
            const pageSelector = document.getElementById("page-selector");
            if (pageSelector) {
                pageSelector.value = pageNum;
            }
            
            // Update navigation
            updatePageNavigation();
            
            // Update page dots
            updatePageDots();
            
            // Fade in new content
            content.style.opacity = "1";
        }, 200);
    }
    
    function updatePageNavigation() {
        // Update arrow buttons
        const prevBtn = document.getElementById("prev-btn-bottom");
        const nextBtn = document.getElementById("next-btn-bottom");
        if (prevBtn) prevBtn.disabled = currentPage === 1;
        if (nextBtn) nextBtn.disabled = currentPage === totalPages;
        
        // Update page numbers
        const pageNumbers = document.querySelectorAll(".page-number");
        pageNumbers.forEach(btn => {
            const pageNum = parseInt(btn.dataset.page);
            if (pageNum === currentPage) {
                btn.classList.add("active");
            } else {
                btn.classList.remove("active");
            }
        });
        
        // Update dynamic page numbers if many pages
        if (totalPages > 7) {
            updateDynamicPageNumbers();
        }
    }
    
    function updateDynamicPageNumbers() {
        const container = document.querySelector(".page-numbers");
        if (!container) return;
        
        let html = "";
        
        if (currentPage <= 4) {
            // Show first 5 pages + last
            for (let i = 1; i <= Math.min(5, totalPages - 1); i++) {
                html += \'<button class="page-number \' + (i === currentPage ? \'active\' : \'\') + \'" onclick="goToPage(\' + i + \')" data-page="\' + i + \'">\' + i + \'</button>\';
            }
            if (totalPages > 6) html += \'<span class="page-ellipsis">...</span>\';
            html += \'<button class="page-number \' + (currentPage === totalPages ? \'active\' : \'\') + \'" onclick="goToPage(\' + totalPages + \')" data-page="\' + totalPages + \'">\' + totalPages + \'</button>\';
        } else if (currentPage >= totalPages - 3) {
            // Show first + last 5 pages
            html += \'<button class="page-number" onclick="goToPage(1)" data-page="1">1</button>\';
            html += \'<span class="page-ellipsis">...</span>\';
            for (let i = totalPages - 4; i <= totalPages; i++) {
                html += \'<button class="page-number \' + (i === currentPage ? \'active\' : \'\') + \'" onclick="goToPage(\' + i + \')" data-page="\' + i + \'">\' + i + \'</button>\';
            }
        } else {
            // Show first + current-1, current, current+1 + last
            html += \'<button class="page-number" onclick="goToPage(1)" data-page="1">1</button>\';
            html += \'<span class="page-ellipsis">...</span>\';
            for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                html += \'<button class="page-number \' + (i === currentPage ? \'active\' : \'\') + \'" onclick="goToPage(\' + i + \')" data-page="\' + i + \'">\' + i + \'</button>\';
            }
            html += \'<span class="page-ellipsis">...</span>\';
            html += \'<button class="page-number" onclick="goToPage(\' + totalPages + \')" data-page="\' + totalPages + \'">\' + totalPages + \'</button>\';
        }
        
        container.innerHTML = html;
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