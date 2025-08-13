<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
        <nav class="admin-nav">
            <h1>CMS Admin</h1>
            <ul>
                <li class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <a href="/admin/dashboard.php">Dashboard</a>
                </li>
                <li class="<?= $currentPage === 'articles' ? 'active' : '' ?>">
                    <a href="/admin/articles.php">Articles</a>
                </li>
                <li class="<?= $currentPage === 'photobooks' ? 'active' : '' ?>">
                    <a href="/admin/photobooks.php">Photobooks</a>
                </li>
                <li class="<?= $currentPage === 'menus' ? 'active' : '' ?>">
                    <a href="/admin/menus.php">Menus</a>
                </li>
                <li class="<?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <a href="/admin/settings.php">Settings</a>
                </li>
                <li class="<?= $currentPage === 'sort' ? 'active' : '' ?>">
                    <a href="/admin/sort.php">Sort Content</a>
                </li>
                <li class="<?= $currentPage === 'upload' ? 'active' : '' ?>">
                    <a href="/admin/upload.php">Upload Files</a>
                </li>
                <li class="<?= $currentPage === 'versions' ? 'active' : '' ?>">
                    <a href="/admin/versions.php">Version History</a>
                </li>
                <li>
                    <a href="/admin/logout.php">Logout</a>
                </li>
            </ul>
        </nav>