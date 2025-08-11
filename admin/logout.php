<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';

session_start();
Auth::logout();
header('Location: /admin/login');
exit;