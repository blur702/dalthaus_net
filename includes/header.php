<?php
/**
 * Reusable header template for all front-end pages
 * Includes site header, navigation menu, and common head elements
 */

// Ensure we have database connection
if (!isset($pdo)) {
    require_once __DIR__ . '/database.php';
    $pdo = Database::getInstance();
}

// Get menu items if not already loaded
if (!isset($topMenu)) {
    $topMenu = $pdo->query("
        SELECT * FROM menu_items
        WHERE menu_location = 'top' 
        AND is_active = TRUE
        ORDER BY sort_order
    ")->fetchAll();
}

// Get site settings if not already loaded
if (!isset($settings)) {
    $settings = [];
    $result = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    foreach ($result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Set default page title if not provided
if (!isset($pageTitle)) {
    $pageTitle = $settings['site_title'] ?? 'Dalthaus.net';
} else {
    $pageTitle = $pageTitle . ' - ' . ($settings['site_title'] ?? 'Dalthaus.net');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css">
    <?php if (isset($additionalStyles)): ?>
    <?= $additionalStyles ?>
    <?php endif; ?>
</head>
<body<?php if (!empty($bodyClass)): ?> class="<?= htmlspecialchars($bodyClass) ?>"<?php endif; ?>>
    <div class="page-wrapper">
        <!-- Header with site title and hamburger menu -->
        <header class="site-header" <?php if (!empty($settings['header_image'])): ?>
            style="background-image: linear-gradient(<?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>, <?= $settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)' ?>), url('<?= htmlspecialchars($settings['header_image']) ?>'); background-size: cover; background-position: <?= $settings['header_position'] ?? 'center center' ?>; height: <?= $settings['header_height'] ?? '200' ?>px;"
        <?php endif; ?>>
            <div class="header-content">
                <div class="header-text" style="color: <?= $settings['header_text_color'] ?? '#ffffff' ?>;">
                    <h1 class="site-title">
                        <a href="/" style="color: <?= $settings['header_text_color'] ?? '#ffffff' ?>; text-decoration: none;">
                            <?= $settings['site_title'] ?? 'Dalthaus.net' ?>
                        </a>
                    </h1>
                    <?php if (!empty($settings['site_motto'])): ?>
                    <p class="site-motto" style="color: <?= $settings['header_text_color'] ?? '#ffffff' ?>;"><?= $settings['site_motto'] ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Hamburger menu button (always visible) -->
                <button class="hamburger-menu" id="hamburger-menu" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
        
        <!-- Slide-out navigation menu -->
        <nav class="slide-menu" id="slide-menu">
            <ul>
                <?php foreach ($topMenu as $item): ?>
                <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>