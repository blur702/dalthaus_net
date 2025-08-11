<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Placeholder Test - Dalthaus.net</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/public.css">
    <style>
        body {
            background: #f5f5f5;
            padding: 2rem;
        }
        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 2rem;
        }
        h2 {
            color: #667eea;
            margin: 2rem 0 1rem;
            font-size: 1.2rem;
        }
        .test-section {
            margin-bottom: 3rem;
        }
        .description {
            color: #666;
            margin-bottom: 1rem;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>Image Placeholder Demo</h1>
        <p class="description">Minimalistic placeholders with 4:3 aspect ratio for missing images</p>
        
        <div class="test-section">
            <h2>Small Placeholder (300px max)</h2>
            <div class="image-placeholder small"></div>
        </div>
        
        <div class="test-section">
            <h2>Medium Placeholder (500px max)</h2>
            <div class="image-placeholder medium"></div>
        </div>
        
        <div class="test-section">
            <h2>Large Placeholder (full width)</h2>
            <div class="image-placeholder large"></div>
        </div>
        
        <div class="test-section book-content">
            <h2>Photobook Layout Examples</h2>
            
            <h3>Left-aligned Image</h3>
            <div class="image-placeholder img-left"></div>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
            <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.</p>
            
            <div style="clear: both; margin: 3rem 0;"></div>
            
            <h3>Right-aligned Image</h3>
            <div class="image-placeholder img-right"></div>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
            <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.</p>
            
            <div style="clear: both; margin: 3rem 0;"></div>
            
            <h3>Center Image</h3>
            <div class="image-placeholder img-center"></div>
            
            <h3>Full Width Image</h3>
            <p>This placeholder extends beyond the normal content boundaries:</p>
        </div>
        
        <div class="test-section" style="overflow: visible;">
            <div class="book-content">
                <div class="image-placeholder img-full"></div>
            </div>
        </div>
    </div>
</body>
</html>