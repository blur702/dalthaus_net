<?php
declare(strict_types=1);

// Get error code from query parameter or default to 404
$error_code = isset($_GET['code']) ? (int)$_GET['code'] : (isset($_SERVER['REDIRECT_STATUS']) ? (int)$_SERVER['REDIRECT_STATUS'] : 404);

// Define friendly error messages
$error_messages = [
    400 => [
        'title' => 'Bad Request',
        'message' => 'The request could not be understood. Please check and try again.',
        'icon' => '🤔'
    ],
    401 => [
        'title' => 'Authorization Required',
        'message' => 'You need to be logged in to access this page.',
        'icon' => '🔐'
    ],
    403 => [
        'title' => 'Access Forbidden',
        'message' => 'You don\'t have permission to access this resource.',
        'icon' => '🚫'
    ],
    404 => [
        'title' => 'Page Not Found',
        'message' => 'The page you\'re looking for doesn\'t exist or has been moved.',
        'icon' => '🔍'
    ],
    500 => [
        'title' => 'Server Error',
        'message' => 'Something went wrong on our end. Please try again later.',
        'icon' => '⚠️'
    ],
    503 => [
        'title' => 'Service Unavailable',
        'message' => 'We\'re temporarily offline for maintenance. Please check back soon.',
        'icon' => '🔧'
    ]
];

// Default error for unknown codes
$default_error = [
    'title' => 'Something Went Wrong',
    'message' => 'An unexpected error occurred. Please try again or return to the homepage.',
    'icon' => '❓'
];

// Get the appropriate error message
$error = isset($error_messages[$error_code]) ? $error_messages[$error_code] : $default_error;

// Set the appropriate HTTP response code
http_response_code($error_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $error_code ?> - <?= htmlspecialchars($error['title']) ?> | Dalthaus.net</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Gelasio', serif;
            background: rgb(248, 248, 248);
            color: rgb(20, 20, 20);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border: 1px solid rgba(20, 20, 20, 0.1);
            border-radius: 2px;
            padding: 3rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 2px 10px rgba(20, 20, 20, 0.05);
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        .error-code {
            font-size: 5rem;
            font-weight: 600;
            font-family: 'Arimo', sans-serif;
            color: rgb(20, 20, 20);
            margin-bottom: 0.5rem;
            line-height: 1;
            opacity: 0.2;
        }
        
        .error-title {
            font-size: 1.75rem;
            font-family: 'Arimo', sans-serif;
            color: rgb(20, 20, 20);
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.1rem;
            color: rgb(20, 20, 20);
            opacity: 0.7;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .error-button {
            padding: 0.75rem 1.5rem;
            border-radius: 2px;
            text-decoration: none;
            font-family: 'Arimo', sans-serif;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            border: 1px solid rgba(20, 20, 20, 0.2);
        }
        
        .error-button-primary {
            background: rgb(20, 20, 20);
            color: white;
        }
        
        .error-button-primary:hover {
            background: rgb(40, 40, 40);
            transform: translateY(-1px);
        }
        
        .error-button-secondary {
            background: white;
            color: rgb(20, 20, 20);
        }
        
        .error-button-secondary:hover {
            background: rgb(248, 248, 248);
        }
        
        .error-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(20, 20, 20, 0.1);
            color: rgb(20, 20, 20);
            opacity: 0.5;
            font-size: 0.9rem;
        }
        
        .error-footer a {
            color: rgb(20, 20, 20);
            opacity: 0.7;
        }
        
        .error-footer a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .error-container {
                padding: 2rem;
            }
            
            .error-code {
                font-size: 3.5rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .error-icon {
                font-size: 3rem;
            }
            
            .error-actions {
                flex-direction: column;
            }
            
            .error-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><?= $error['icon'] ?></div>
        <div class="error-code"><?= $error_code ?></div>
        <h1 class="error-title"><?= htmlspecialchars($error['title']) ?></h1>
        <p class="error-message"><?= htmlspecialchars($error['message']) ?></p>
        
        <div class="error-actions">
            <a href="/" class="error-button error-button-primary">Go to Homepage</a>
            <a href="javascript:history.back()" class="error-button error-button-secondary">Go Back</a>
        </div>
        
    </div>
</body>
</html>