<?php
/**
 * Development Server Router
 * 
 * Routes requests for PHP's built-in development server.
 * Handles clean URLs and mimics Apache .htaccess behavior.
 * This file is only used with: php -S localhost:8000 router.php
 * 
 * Features:
 * - Static file serving (CSS, JS, images)
 * - Clean URL routing for public pages
 * - Direct PHP file access for admin pages
 * - 404 handling for missing resources
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */

// Parse the request URI to get the path
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/**
 * Static File Handling
 * 
 * Let PHP's built-in server handle static files directly.
 * Matches common web assets by file extension.
 */
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|woff|woff2|ttf|svg|webp)$/', $uri)) {
    if (file_exists(__DIR__ . $uri)) {
        // Return false to let PHP handle the static file
        return false;
    }
}

/**
 * Direct PHP File Access
 * 
 * Allow direct access to PHP files that exist on disk.
 * Used for admin pages and test scripts.
 */
if (preg_match('/\.php$/', $uri) && file_exists(__DIR__ . $uri)) {
    // Return false to let PHP execute the file directly
    return false;
}

/**
 * Public Page Routing
 * 
 * Routes clean URLs to public PHP controllers.
 * Handles: /articles, /photobooks, /article/*, /photobook/*
 */
if (preg_match('/^\/(articles|photobooks|article|photobook|error)/', $uri)) {
    // Build path to public controller
    $file = __DIR__ . '/public' . explode('?', $uri)[0] . '.php';
    
    if (file_exists($file)) {
        // Set up params array for the controller
        $_GET['params'] = array_filter(explode('/', trim($uri, '/')));
        
        // Include and execute the controller
        require_once $file;
        return true;
    }
}

/**
 * Content with Alias Routing
 * 
 * Special handling for article and photobook URLs with aliases.
 * Extracts the alias and passes it to the appropriate controller.
 * Example: /article/my-article-title -> article.php with alias parameter
 */
if (preg_match('/^\/(article|photobook)\/([^\/]+)/', $uri, $matches)) {
    // Extract content type and alias from URL
    $contentType = $matches[1];  // 'article' or 'photobook'
    $alias = $matches[2];         // The URL alias/slug
    
    // Set params for the controller
    $_GET['params'] = [$alias];
    
    // Build path to controller
    $file = __DIR__ . '/public/' . $contentType . '.php';
    
    if (file_exists($file)) {
        // Include and execute the controller
        require_once $file;
        return true;
    }
}

/**
 * Homepage and 404 Handling
 * 
 * Routes root URL to homepage.
 * All other URLs show 404 error page.
 */
if ($uri === '/' || $uri === '/index.php') {
    // Load homepage
    require_once __DIR__ . '/public/index.php';
} else {
    // Show 404 error page for unmatched routes
    $_GET['code'] = 404;
    require_once __DIR__ . '/public/error.php';
}

// Return true to indicate we handled the request
return true;