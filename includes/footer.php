<?php
/**
 * Reusable footer template for all front-end pages
 * Includes footer menu, copyright, and common scripts
 */

// Get bottom menu items if not already loaded
if (!isset($bottomMenu)) {
    $bottomMenu = $pdo->query("
        SELECT * FROM menu_items
        WHERE menu_location = 'bottom' 
        AND is_active = TRUE
        ORDER BY sort_order
    ")->fetchAll();
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
                <p><?= $settings['copyright_notice'] ?? '© ' . date('Y') . ' Dalthaus.net. All rights reserved.' ?></p>
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
    
    <?php if (isset($additionalScripts)): ?>
    <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>