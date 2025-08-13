<?php
/**
 * Reusable footer template for all front-end pages
 * Includes footer menu, copyright, and common scripts
 */

// Ensure we have database connection
if (!isset($pdo)) {
    require_once __DIR__ . '/database.php';
    $pdo = Database::getInstance();
}

// Get bottom menu items if not already loaded
if (!isset($bottomMenu)) {
    $bottomMenu = $pdo->query("
        SELECT * FROM menu_items
        WHERE menu_location = 'bottom' 
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
?>
        <!-- Footer with bottom menu -->
        <footer class="site-footer">
            <?php if ($bottomMenu && count($bottomMenu) > 0): ?>
            <nav class="bottom-menu" role="navigation" aria-label="Footer navigation">
                <ul>
                    <?php foreach ($bottomMenu as $item): ?>
                    <li><a href="<?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['title']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <div class="footer-info">
                <?= $settings['footer_text'] ?? '<p>&copy; ' . date('Y') . ' ' . ($settings['site_title'] ?? 'Dalthaus.net') . '. All rights reserved.</p>' ?>
            </div>
        </footer>
    </div>
    
    <!-- Hamburger menu script -->
    <script>
        document.getElementById('hamburger-menu').addEventListener('click', function() {
            const menu = document.getElementById('slide-menu');
            const hamburger = this;
            menu.classList.toggle('active');
            hamburger.classList.toggle('active');
        });
        
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('slide-menu');
            const hamburger = document.getElementById('hamburger-menu');
            if (!menu.contains(event.target) && !hamburger.contains(event.target)) {
                menu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    </script>
    
    <?php if (!empty($settings['google_analytics'])): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($settings['google_analytics']) ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= htmlspecialchars($settings['google_analytics']) ?>');
    </script>
    <?php endif; ?>
    
    <?php if (isset($additionalScripts)): ?>
    <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>