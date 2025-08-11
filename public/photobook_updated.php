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

// Get photobook from new photobooks table
$stmt = $pdo->prepare("
    SELECT * FROM photobooks 
    WHERE alias = ? 
    AND status = 'published' 
    AND deleted_at IS NULL
");
$stmt->execute([$alias]);
$photobook = $stmt->fetch();

if (!$photobook) {
    showError(404);
}

// Parse photobook content into pages/chapters
$pages = preg_split('/<!-- page -->/', $photobook['body'] ?? '');
$totalPages = count($pages);

// Clean up pages - remove empty ones
$pages = array_filter($pages, function($page) {
    return !empty(trim($page));
});
$pages = array_values($pages); // Re-index
$totalPages = count($pages) ?: 1;

// If no page breaks, treat entire content as single page
if ($totalPages === 0) {
    $pages = [$photobook['body'] ?? ''];
    $totalPages = 1;
}

// Set page title
$pageTitle = $photobook['title'];

// Additional styles for photobook viewer
$additionalStyles = '
<style>
    .book-content {
        transition: opacity 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    
    @media print {
        .photobook-controls,
        .site-header,
        .site-footer,
        .article-nav {
            display: none;
        }
        
        .book-content {
            padding: 0;
        }
        
        .photobook-viewer {
            box-shadow: none;
        }
    }
</style>
';

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
    
    <main id="main" class="site-main">
        <div class="container">
            <div class="photobook-viewer">
                <div class="book-header">
                    <h1 class="book-title"><?= htmlspecialchars($photobook['title']) ?></h1>
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="photobook-controls">
                    <button class="nav-button" onclick="navigatePage(-1)" aria-label="Previous page">
                        <span class="arrow">←</span> Previous
                    </button>
                    <div class="page-indicator">
                        Page <span id="current-page">1</span> of <?= $totalPages ?>
                    </div>
                    <button class="nav-button" onclick="navigatePage(1)" aria-label="Next page">
                        Next <span class="arrow">→</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <article id="photobook-content" class="book-content" role="main" aria-live="polite">
                    <!-- Story pages will be loaded here -->
                </article>
                
                <?php if ($totalPages > 1): ?>
                <div class="photobook-controls bottom-controls">
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
            </div>
            
            <nav class="article-nav">
                <a href="/photobooks" class="back-link">← Back to Photo Books</a>
            </nav>
        </div>
    </main>
    
<?php
// Additional scripts for photobook navigation
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
            
            // Scroll to top of viewer
            document.querySelector(".photobook-viewer").scrollIntoView({ behavior: "smooth" });
        }
    }
    
    function goToPage(page) {
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            loadPage(currentPage);
            updateHistory();
            
            // Scroll to top of viewer
            document.querySelector(".photobook-viewer").scrollIntoView({ behavior: "smooth" });
        }
    }
    
    function loadPage(pageNum) {
        const content = document.getElementById("photobook-content");
        const currentPageIndicator = document.getElementById("current-page");
        
        // Fade out current content
        content.style.opacity = "0";
        
        setTimeout(() => {
            // Update content
            content.innerHTML = pages[pageNum - 1] || "";
            
            // Update page indicator
            if (currentPageIndicator) {
                currentPageIndicator.textContent = pageNum;
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
        const url = `/photobook/${slug}#page-${currentPage}`;
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