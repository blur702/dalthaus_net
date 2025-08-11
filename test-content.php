<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// Test content with missing images
$testContent = '
<h2>Testing Image Placeholders</h2>
<p>This is a test article with some missing images to demonstrate the placeholder functionality.</p>

<img src="/uploads/missing-photo1.jpg" alt="Missing photo" class="img-left">
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. This paragraph has a left-aligned image that doesn\'t exist, so it should show a placeholder.</p>

<p>Another paragraph of text here.</p>

<img src="/uploads/missing-photo2.jpg" alt="Another missing photo" class="img-center">
<p>This center image should also be replaced with a placeholder.</p>

<img src="/uploads/missing-photo3.jpg" alt="Full width missing" class="img-full">
<p>And finally, a full-width image placeholder.</p>
';

$processedContent = processContentImages($testContent);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Content Processing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css">
    <style>
        body { padding: 2rem; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; }
        h1 { color: #2c3e50; margin-bottom: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Testing processContentImages Function</h1>
        <div class="book-content">
            <?= $processedContent ?>
        </div>
    </div>
</body>
</html>