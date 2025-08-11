<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (Auth::isLoggedIn()) {
    header('Location: /admin');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } elseif (Auth::login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: /admin');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dalthaus CMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        body.login-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            font-family: 'Arimo', sans-serif;
        }
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-box h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .login-box .form-group {
            margin-bottom: 20px;
        }
        .login-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .login-box button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .login-box button:hover {
            background: #764ba2;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-box">
        <h1>Admin Login</h1>
        <form method="post">
            <?php if ($error): ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required autofocus>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>