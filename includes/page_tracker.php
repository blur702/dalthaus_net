<?php
/**
 * Page Tracking Functions
 * 
 * Handles extraction and storage of page break information for multi-page content.
 * Enables creation of page navigation menus and table of contents.
 */

/**
 * Extract page information from content
 * 
 * Parses content for page breaks and extracts titles for each page.
 * Returns an array of page information including titles and positions.
 * 
 * @param string $content HTML content with <!-- page --> markers
 * @return array Array of page information
 */
function extractPageInfo($content) {
    if (empty($content)) {
        return [
            'pages' => [['page' => 1, 'title' => 'Page 1', 'position' => 0]],
            'count' => 1
        ];
    }
    
    // Split content by page breaks
    $pages = explode('<!-- page -->', $content);
    $pageCount = count($pages);
    
    // If no page breaks, return single page
    if ($pageCount == 1) {
        $title = extractPageTitle($content, 1);
        return [
            'pages' => [['page' => 1, 'title' => $title, 'position' => 0]],
            'count' => 1
        ];
    }
    
    // Extract information for each page
    $pageInfo = [];
    $currentPosition = 0;
    
    foreach ($pages as $index => $pageContent) {
        $pageNum = $index + 1;
        $title = extractPageTitle($pageContent, $pageNum);
        
        $pageInfo[] = [
            'page' => $pageNum,
            'title' => $title,
            'position' => $currentPosition
        ];
        
        // Update position for next page (add length of current content + separator)
        if ($index < count($pages) - 1) {
            $currentPosition += strlen($pageContent) + strlen('<!-- page -->');
        }
    }
    
    return [
        'pages' => $pageInfo,
        'count' => $pageCount
    ];
}

/**
 * Extract a title from page content
 * 
 * Attempts to extract a meaningful title from page content by looking for:
 * 1. First heading (h2-h6)
 * 2. First paragraph (truncated)
 * 3. Default "Page X" if nothing found
 * 
 * @param string $content Page HTML content
 * @param int $pageNum Page number for default title
 * @return string Extracted or default title
 */
function extractPageTitle($content, $pageNum = 1) {
    $title = '';
    
    // Try to extract first heading as title (h2-h6, not h1 as it's reserved)
    if (preg_match('/<h[2-6][^>]*>(.*?)<\/h[2-6]>/i', $content, $matches)) {
        $title = strip_tags(html_entity_decode($matches[1]));
    } 
    // Fall back to first paragraph (truncated)
    elseif (preg_match('/<p[^>]*>(.*?)<\/p>/i', $content, $matches)) {
        $text = strip_tags(html_entity_decode($matches[1]));
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Truncate to reasonable length
        if (strlen($text) > 50) {
            $title = substr($text, 0, 47) . '...';
        } else {
            $title = $text;
        }
    }
    
    // Clean up the title
    $title = trim($title);
    
    // Default title if nothing found or too short
    if (empty($title) || strlen($title) < 3) {
        $title = "Page $pageNum";
    }
    
    return $title;
}

/**
 * Update page tracking for content
 * 
 * Updates the page_breaks and page_count columns for a content item.
 * Should be called whenever content is saved or updated.
 * Works with both content table and legacy tables (articles, photobooks).
 * 
 * @param PDO $pdo Database connection
 * @param int|string $contentId Content ID to update
 * @param string $body Content body with page breaks
 * @param string $table Table name (default: 'content')
 * @return bool Success status
 */
function updatePageTracking($pdo, $contentId, $body, $table = 'content') {
    try {
        $pageData = extractPageInfo($body);
        
        // Check which table we're updating
        if ($table === 'articles' || $table === 'photobooks') {
            // Legacy tables
            $stmt = $pdo->prepare("UPDATE $table 
                SET page_breaks = ?, page_count = ? 
                WHERE id = ?");
        } else {
            // Content table
            $stmt = $pdo->prepare("UPDATE content 
                SET page_breaks = ?, page_count = ? 
                WHERE id = ?");
        }
        
        $stmt->execute([
            json_encode($pageData['pages']),
            $pageData['count'],
            $contentId
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to update page tracking for $table $contentId: " . $e->getMessage());
        return false;
    }
}

/**
 * Get page information for content
 * 
 * Retrieves stored page break information for a content item.
 * Returns null if no page data is stored.
 * Works with both content table and legacy tables.
 * 
 * @param PDO $pdo Database connection
 * @param int|string $contentId Content ID
 * @param string $table Table name (default: 'content')
 * @return array|null Page information or null
 */
function getPageInfo($pdo, $contentId, $table = 'content') {
    try {
        // Try to determine table from ID format if not specified
        if ($table === 'content') {
            // Check if this ID exists in articles or photobooks tables
            $checkStmt = $pdo->prepare("SELECT 'articles' as tbl FROM articles WHERE id = ? 
                                        UNION 
                                        SELECT 'photobooks' as tbl FROM photobooks WHERE id = ?");
            $checkStmt->execute([$contentId, $contentId]);
            $result = $checkStmt->fetch();
            if ($result) {
                $table = $result['tbl'];
            }
        }
        
        $stmt = $pdo->prepare("SELECT page_breaks, page_count FROM $table WHERE id = ?");
        $stmt->execute([$contentId]);
        $row = $stmt->fetch();
        
        if ($row && $row['page_breaks']) {
            return [
                'pages' => json_decode($row['page_breaks'], true),
                'count' => $row['page_count']
            ];
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Failed to get page info for $table $contentId: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate page navigation menu HTML
 * 
 * Creates an HTML menu for navigating between pages in multi-page content.
 * Can be styled with CSS to create dropdown, sidebar, or inline navigation.
 * 
 * @param array $pageInfo Page information from getPageInfo()
 * @param int $currentPage Currently active page number
 * @param string $baseUrl Base URL for page links
 * @return string HTML for page navigation menu
 */
function generatePageMenu($pageInfo, $currentPage = 1, $baseUrl = '') {
    if (!$pageInfo || $pageInfo['count'] <= 1) {
        return '';
    }
    
    $html = '<nav class="page-menu" aria-label="Page navigation">';
    $html .= '<div class="page-menu-header">Pages</div>';
    $html .= '<ul class="page-menu-list">';
    
    foreach ($pageInfo['pages'] as $page) {
        $pageNum = $page['page'];
        $title = htmlspecialchars($page['title']);
        $url = $baseUrl . '#page-' . $pageNum;
        $activeClass = ($pageNum == $currentPage) ? ' class="active"' : '';
        
        $html .= "<li$activeClass>";
        $html .= "<a href=\"$url\" data-page=\"$pageNum\">";
        $html .= "<span class=\"page-num\">$pageNum.</span> ";
        $html .= "<span class=\"page-title\">$title</span>";
        $html .= "</a></li>";
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Generate page breadcrumb navigation
 * 
 * Creates a simple breadcrumb showing current page position.
 * 
 * @param array $pageInfo Page information
 * @param int $currentPage Current page number
 * @return string HTML for breadcrumb
 */
function generatePageBreadcrumb($pageInfo, $currentPage = 1) {
    if (!$pageInfo || $pageInfo['count'] <= 1) {
        return '';
    }
    
    $currentTitle = 'Page ' . $currentPage;
    foreach ($pageInfo['pages'] as $page) {
        if ($page['page'] == $currentPage) {
            $currentTitle = htmlspecialchars($page['title']);
            break;
        }
    }
    
    $html = '<div class="page-breadcrumb">';
    $html .= '<span class="current-page">' . $currentTitle . '</span>';
    $html .= '<span class="page-position"> (Page ' . $currentPage . ' of ' . $pageInfo['count'] . ')</span>';
    $html .= '</div>';
    
    return $html;
}