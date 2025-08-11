<?php
declare(strict_types=1);

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/router.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$router = new Router();

// Public routes
$router->add('/', 'public/index.php');
$router->add('/article/([a-z0-9-]+)', 'public/article.php');
$router->add('/photobook/([a-z0-9-]+)', 'public/photobook.php');
$router->add('/download/(.+)', 'public/download.php');

// Admin routes
$router->add('/admin', 'admin/dashboard.php');
$router->add('/admin/login', 'admin/login.php');
$router->add('/admin/logout', 'admin/logout.php');
$router->add('/admin/articles', 'admin/articles.php');
$router->add('/admin/photobooks', 'admin/photobooks.php');
$router->add('/admin/menus', 'admin/menus.php');
$router->add('/admin/sort', 'admin/sort.php');
$router->add('/admin/upload', 'admin/upload.php');
$router->add('/admin/import', 'admin/import.php');
$router->add('/admin/versions', 'admin/versions.php');

// API routes
$router->add('/admin/api/autosave', 'admin/api/autosave.php', ['POST']);
$router->add('/admin/api/sort', 'admin/api/sort.php', ['POST']);
$router->add('/admin/api/import', 'admin/api/import.php', ['POST']);
$router->add('/api/photobook-page', 'public/api/photobook-page.php');

// Get the URI from the request
// Handle both built-in server and Apache/MAMP routing
if (isset($_GET['route'])) {
    // Apache with mod_rewrite passes route as parameter
    $uri = '/' . ltrim($_GET['route'], '/');
} else {
    // PHP built-in server or direct access
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Remove base path if site is in a subdirectory
    // Adjust this if your site is in a subdirectory like /dalthaus_net/
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
        $uri = substr($uri, strlen($basePath));
    }
}

$uri = $uri ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);