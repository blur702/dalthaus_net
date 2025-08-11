# Dalthaus.net CMS

A custom PHP content management system with document import, versioning, autosave, and drag-drop sorting capabilities.

## Features

- **Content Management**: Articles and Photobooks with full CRUD operations
- **Version Control**: Automatic versioning with restore capability
- **Autosave**: Every 30 seconds for content being edited
- **Menu Management**: Drag-and-drop menu ordering for top and bottom menus
- **Document Import**: Convert Word and PDF documents to HTML
- **File Uploads**: Secure file upload with attachment management
- **History API**: Modern navigation for photobooks with browser history support
- **Responsive Design**: Mobile-friendly admin and public interfaces
- **Security**: CSRF protection, secure sessions, input validation
- **Performance**: File-based caching for public pages

## Requirements

- PHP 8.3+
- MySQL 5.7+
- Python 3 (for document conversion)
- Modern web browser with JavaScript enabled

## Installation

1. Clone or download the repository to your web server
2. Configure database settings in `includes/config.php`
3. Run the setup script:
   ```bash
   php setup.php
   ```
4. Start the development server:
   ```bash
   php -S localhost:8000 -t . router.php
   ```
5. Access the admin panel at `http://localhost:8000/admin`
   - Default username: `admin`
   - Default password: `130Bpm`
   - You'll be prompted to change the password on first login

## Production Deployment

1. Upload all files to your web hosting
2. Set proper permissions:
   ```bash
   chmod 755 uploads cache logs temp
   chmod 644 .htaccess
   ```
3. Update `includes/config.php`:
   - Set `ENV` to 'production'
   - Update database credentials
   - Configure `PYTHON_PATH` if needed
4. Run `php setup.php` to initialize the database
5. Configure your web server to use `.htaccess` rules
6. Set up a cron job for temp file cleanup:
   ```bash
   0 * * * * php /path/to/your/site/includes/functions.php --cleanup
   ```

## Directory Structure

```
/
├── includes/          # Core PHP files
│   ├── config.php    # Configuration constants
│   ├── database.php  # Database connection and setup
│   ├── auth.php      # Authentication functions
│   ├── functions.php # Helper functions
│   ├── router.php    # URL routing
│   └── upload.php    # File upload handler
├── admin/            # Admin panel files
├── public/           # Public-facing pages
├── templates/        # PHP templates
├── assets/           # CSS, JavaScript, fonts
├── uploads/          # User uploads
├── cache/            # File cache
├── logs/             # Application logs
├── temp/             # Temporary files
├── scripts/          # Python converter script
└── tests/            # PHPUnit and Playwright tests
```

## Usage

### Creating Content

1. Log into the admin panel
2. Navigate to Articles or Photobooks
3. Click "New Article" or "New Photobook"
4. Enter your content using the TinyMCE editor
5. Set status to "Published" when ready
6. Content autosaves every 30 seconds

### Managing Menus

1. Go to Admin → Menus
2. Select content to add to menus
3. Drag and drop to reorder items
4. Enable/disable items as needed

### Importing Documents

1. Go to Admin → Import Documents
2. Upload a Word (.docx) or PDF file
3. Review the converted HTML
4. Either copy the HTML or create a new article directly

### Photobook Navigation

Photobooks support multi-page navigation:
- Use `<!-- page -->` to separate pages in the HTML
- Navigation uses History API for smooth transitions
- Keyboard shortcuts: Arrow keys for navigation, ESC to close lightbox

## Security Features

- PDO prepared statements prevent SQL injection
- CSRF tokens on all forms
- XSS prevention through output escaping
- Session regeneration on privilege changes
- File upload validation (type, size, extension)
- Path traversal protection
- Rate limiting on login attempts
- No sensitive data in logs

## Performance Optimization

- File-based caching for public pages
- Lazy loading for images
- Minimal CSS/JS bundles
- Automatic log rotation at 5000 lines
- Cache invalidation on content changes

## Testing

Run the test suite:
```bash
# Unit tests
./vendor/bin/phpunit tests/

# E2E tests
npx playwright test
```

## Troubleshooting

### Document conversion not working
- Check Python is installed: `python3 --version`
- Install required libraries: `pip install pypandoc pdfplumber`
- Verify `PYTHON_PATH` in config.php

### Autosave not working
- Check browser console for JavaScript errors
- Verify AJAX endpoint is accessible
- Check `content_versions` table in database

### Cache issues
- Clear cache: `rm -rf cache/*`
- Disable cache in development by setting `CACHE_ENABLED` to false

## License

Copyright © 2025 Dalthaus.net. All rights reserved.

## Support

For issues or questions, check the logs in the `logs/` directory for error details.