# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project: Dalthaus.net Photography CMS

Custom PHP 8.3 CMS for photography portfolio with document import, versioning, autosave, and drag-drop sorting. No frameworks - vanilla PHP optimized for shared hosting.

## Commands

### Development Server
```bash
# Start server (single port for both public and admin)
php -S localhost:8000 router.php

# Access URLs:
# Public:  http://localhost:8000/
# Admin:   http://localhost:8000/admin/login.php
# Default: admin / 130Bpm
```

### Database Setup
```bash
# Initial setup (creates DB, tables, sample content)
php setup.php

# Manual DB operations
php includes/database.php --setup

# Clear cache
rm -rf cache/*
```

### Testing
```bash
# Playwright E2E tests
npm install  # first time only
npx playwright test

# Document converter test
python3 scripts/converter.py test.docx
```

## Architecture

### Database Schema Relationships
```
content (unified table)
├── type: 'article' | 'photobook' | 'page'
├── status: 'draft' | 'published'
├── deleted_at: soft delete timestamp
└── sort_order: drag-drop ordering

content_versions
├── content_id → content.id
├── is_autosave: TRUE for 30-sec saves
└── version_number: increments on manual save

menus
├── location: 'top' | 'bottom'
├── content_id → content.id
└── sort_order: drag-drop position

attachments
└── content_id → content.id
```

### Request Routing Architecture

**Production (Apache/.htaccess)**:
- All URLs → index.php?route=$1
- Clean URLs: /article/my-title → public/article.php with params

**Development (router.php)**:
- Mimics .htaccess behavior
- Static files served directly
- PHP files in /admin/ and /public/ directories

### Critical File Dependencies

**Authentication Flow**:
1. `admin/login.php` → `Auth::login()`
2. `includes/auth.php` → Session creation
3. All admin pages: `Auth::requireAdmin()` at top
4. Session stored in DB `sessions` table

**Content Rendering Pipeline**:
1. Public request → `public/{type}.php`
2. Query `content` table WHERE status='published'
3. Process with `processContentImages()` for placeholders
4. Cache result via `cacheSet()` if production

**Autosave Architecture**:
- `assets/js/autosave.js`: 30-sec timer, TinyMCE integration
- `admin/api/autosave.php`: CSRF-protected endpoint
- Saves to `content_versions` with `is_autosave=TRUE`
- Version restore: `admin/versions.php?content_id=X`

**Document Import Flow**:
1. Upload to `admin/import.php`
2. Python `scripts/converter.py` (requires pypandoc/pdfplumber)
3. Returns JSON with HTML
4. Preview in TinyMCE
5. Create article on confirm

### Theme & Styling System

**Color Palette** (defined in CSS, used consistently):
- Primary Dark: `#2c3e50` (headers, nav)
- Primary Blue: `#3498db` (links, buttons)
- Purple Accent: `#8e44ad` (photobooks)
- Text: `#333` (body text)
- Muted: `#7f8c8d` (meta info)

**Typography**:
- Headlines: 'Arimo' (sans-serif)
- Body: 'Gelasio' (serif)
- Meta: Single line with `·` separator

**Meta Format**: `Don Althaus · Category · Date`

### Security Implementation

**CSRF Protection**:
- All forms: `generateCSRFToken()` in hidden field
- All POST handlers: `validateCSRFToken($_POST['csrf_token'])`
- Tokens stored in `$_SESSION['csrf_token']`

**SQL Injection Prevention**:
- All queries use PDO prepared statements
- No string concatenation in SQL
- Database singleton: `Database::getInstance()`

**File Upload Security**:
- MIME type validation in `ALLOWED_EXTENSIONS`
- Size limit: `UPLOAD_MAX_SIZE` (10MB)
- Stored outside web root when possible

### Performance Optimizations

**Caching Strategy**:
- File-based cache in `cache/` directory
- Key: MD5 hash of request
- TTL: 3600 seconds (1 hour)
- Cleared on content changes via `cacheClear()`

**Database Indexes**:
- `content`: idx_slug, idx_type_status, idx_sort
- `content_versions`: idx_content_version
- `menus`: idx_location_order

### Special Features

**Photobook Pages**:
- Split by `<!-- page -->` markers
- History API navigation (pushState/popState)
- URL: `/photobook/alias#page-2`
- Maintains scroll position

**Drag-Drop Sorting**:
- Sortable.js integration
- AJAX endpoint: `admin/api/sort.php`
- Updates `sort_order` field
- Real-time save with visual feedback

**Version System**:
- Every save creates version
- Autosaves marked with flag
- Restore any version
- Current backs up before restore

## Configuration Constants

Key settings in `includes/config.php`:

```php
DB_HOST = '127.0.0.1'  // Use IP not 'localhost' for MAMP
DB_NAME = 'dalthaus_cms'
ENV = 'development'|'production'
PYTHON_PATH = '/usr/bin/python3'
DEFAULT_ADMIN_USER = 'admin'
DEFAULT_ADMIN_PASS = '130Bpm'
SESSION_LIFETIME = 3600
UPLOAD_MAX_SIZE = 10485760  // 10MB
LOG_MAX_LINES = 5000
CACHE_TTL = 3600
```

## Production Deployment

1. Set `ENV='production'` in config.php
2. Update DB credentials
3. Uncomment HTTPS redirect in .htaccess
4. Run `php setup.php`
5. Change admin password on first login
6. Set directory permissions: 755
7. Configure cron for temp cleanup: `0 * * * * php /path/includes/functions.php --cleanup`

## Common Pitfalls

- **router.php** is ONLY for dev server, not used in production
- **Admin pages** must start with `Auth::requireAdmin()` or will be publicly accessible
- **TinyMCE** content must be processed with `processContentImages()` to handle missing images
- **Autosave** won't work without TinyMCE initialized on #body field
- **Python converter** fails silently if pypandoc/pdfplumber not installed
- **H1 tags** are automatically converted to H2 (H1 reserved for page titles)
- **Page breaks** are detected and converted to `<!-- page -->` markers
- **TinyMCE** configured with GPL license, no upgrade prompts

## Recent Updates (2025-08-11)

### TinyMCE Configuration
All TinyMCE instances now configured with:
- `license_key: 'gpl'` - No upgrade prompts
- `promotion: false`, `branding: false`
- Content styles match front-end exactly (Gelasio body, Arimo headings)
- Page break support with Ctrl+Shift+P shortcut
- Comprehensive `extended_valid_elements` list

### Page Tracking System
- Database columns added: `page_breaks` (JSON), `page_count` (INT)
- Automatic page title extraction from content
- Page selector dropdowns with meaningful titles
- Full navigation UI for multi-page content
- Functions in `includes/page_tracker.php`

### Python Converter Updates
- Auto-detects various page break patterns
- Converts all H1 tags to H2
- `--list-elements` flag shows allowed HTML

### Implementation Notes
Detailed technical documentation available in `IMPLEMENTATION_NOTES.md`