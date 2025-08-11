# DaltHaus.net - Complete Project Specification for Claude Code

## Project Overview
Build a minimalist photography portfolio and article website for a photography professor using **vanilla PHP** (no Laravel - simpler for shared hosting). The site emphasizes ultra-clean design where photography is never compromised by website elements.

## Technical Environment
- **Hosting:** Hosting.com shared hosting
- **Server:** PHP 8.3, MariaDB, file permissions via cPanel
- **Structure:** Everything in `public_html/` folder
- **Access:** cPanel + terminal available

## Database Schema
```sql
-- Articles table
CREATE TABLE articles (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    alias VARCHAR(255) UNIQUE NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    featured_image VARCHAR(255),
    meta_keywords TEXT,
    meta_description TEXT,
    sort_order INT DEFAULT 0,
    status ENUM('draft', 'published', 'deleted') DEFAULT 'draft',
    published_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_status_sort (status, sort_order),
    INDEX idx_alias (alias),
    INDEX idx_published_date (published_date),
    INDEX idx_created_at (created_at)
);

-- Photobooks table  
CREATE TABLE photobooks (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    title VARCHAR(255) NOT NULL,
    alias VARCHAR(255) UNIQUE NOT NULL,
    body LONGTEXT,
    summary TEXT,
    featured_image VARCHAR(255),
    teaser_image VARCHAR(255),
    meta_keywords TEXT,
    meta_description TEXT,
    sort_order INT DEFAULT 0,
    status ENUM('draft', 'published', 'deleted') DEFAULT 'draft',
    published_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_status_sort (status, sort_order),
    INDEX idx_alias (alias),
    INDEX idx_published_date (published_date),
    INDEX idx_created_at (created_at)
);

-- Content versions table (for autosave and version history)
CREATE TABLE content_versions (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    content_type ENUM('article', 'photobook') NOT NULL,
    content_id CHAR(36) NOT NULL,
    version_number INT NOT NULL,
    title VARCHAR(255),
    body LONGTEXT,
    summary TEXT,
    excerpt TEXT,
    meta_keywords TEXT,
    meta_description TEXT,
    is_autosave BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_content_lookup (content_type, content_id),
    INDEX idx_version_number (version_number),
    INDEX idx_created_at (created_at)
);

-- Content attachments table
CREATE TABLE content_attachments (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    content_type ENUM('article', 'photobook') NOT NULL,
    content_id CHAR(36) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_content_lookup (content_type, content_id),
    INDEX idx_filename (filename)
);

-- Simple users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor') DEFAULT 'editor',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Site settings table
CREATE TABLE site_settings (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);

-- Content import temporary files (24hr cleanup)
CREATE TABLE content_import_temp (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cleanup (uploaded_at)
);

-- Menu items table
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_location ENUM('top', 'bottom') NOT NULL,
    title VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Database Auto-Setup Configuration

### Configuration File Structure
**`/includes/config.php`** - Main configuration file containing:
```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'your_username');
define('DB_PASSWORD', 'your_password');
define('DB_NAME', 'dalthaus_cms');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration
define('SITE_URL', 'https://dalthaus.net');
define('SITE_ROOT', '/public_html');
define('UPLOAD_PATH', '/uploads');
define('MAX_UPLOAD_SIZE', '10M');

// Environment Configuration
define('ENVIRONMENT', 'development'); // 'development' or 'production'
define('DEBUG_MODE', true); // Console output in development

// Python Script Configuration
define('PYTHON_SCRIPT_PATH', '/scripts/document-converter.py');
define('TEMP_IMPORT_PATH', '/temp-imports');
define('PYTHON_EXECUTABLE', 'python3'); // or full path if needed

// Testing Configuration
define('TESTING_MODE', false); // Enable for running tests
define('TEST_DATABASE', 'dalthaus_cms_test');
define('PLAYWRIGHT_HEADLESS', true); // Headless browser testing

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('CSRF_TOKEN_LENGTH', 32);

// Default Admin User (for initial setup)
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', '130Bpm');
```

### Auto-Setup Features
- **Database Creation:** Automatically create database if it doesn't exist
- **Table Creation:** Auto-create all required tables with proper indexes
- **Default Data:** Insert initial site settings and default admin user
- **Error Handling:** Graceful fallback if auto-setup fails
- **Setup Verification:** Check and report setup status

### Photobook Content Fields
- **Title:** Main title displayed on the photobook page
- **Featured Image:** Hero image displayed between title and content on individual photobook pages
- **Teaser Image:** 4:3 aspect ratio image for listing pages and homepage teasers
- **Summary:** Descriptive text displayed alongside teaser image on listing/homepage
- **Body:** TinyMCE editor with pagebreak functionality for multi-page photobook stories
  - Custom image upload with dual fields: display image + lightbox image
  - Pagebreak splits content into user-defined pages
- **URL Alias:** User-defined, browser-friendly URL (defaults to title-based slug)
- **Publishing Status:** Draft/Published/Deleted (soft delete with trash bin)
- **Published Date:** Adjustable publication date
- **Meta Information:** Keywords and description for SEO
- **File Attachments:** Document uploads with custom display names
- **Version Control:** Autosave and manual version history with restore capability
- **Text Color:** `rgb(20,20,20)` - Very dark gray
- **Background:** `rgb(248,248,248)` - Almost imperceptible light gray  
- **Overlay Text:** Pure white `#FFFFFF`
- **Header Background:** Admin-configurable background image
- **Header Color Overlay:** Admin-configurable color overlay on header background image
- **Site Identity:** Admin-configurable site title and motto
- **Fonts:** Arimo (headlines/captions), Gelasio (body text) from Google Fonts
- **Navigation:** Hamburger menu ALWAYS (all screen sizes) + admin-configurable top/bottom menus
- **No:** About page, Contact page, comments (unless added via custom menu items)

## File Structure
```
public_html/
├── index.php (homepage)
├── logs/ (error and event logging - 5000 line limit)
├── admin/ (password: 130Bpm)
│   ├── index.php (dashboard)
│   ├── articles.php (CRUD with version control)
│   ├── photobooks.php (CRUD with version control)
│   ├── import-document.php (AJAX endpoint for document conversion)
│   ├── sort-articles.php (drag-drop article ordering)
│   ├── sort-photobooks.php (drag-drop photobook ordering)
│   ├── files.php (attachment management)
│   ├── trash.php (deleted content management)
│   ├── users.php (user management - admin only)
│   ├── profile.php (change password for current user)
│   ├── settings.php (site settings & header image)
│   └── menus.php (menu management with drag-drop)
├── articles.php (article listing page)
├── photobooks.php (photobook listing page)
├── article.php (individual articles)
├── photobook.php (photobook viewer)
├── uploads/ (images & documents - no processing)
├── temp-imports/ (24hr document import files)
├── scripts/
│   └── document-converter.py (Python document to HTML converter)
├── tests/
│   ├── unit/ (PHPUnit tests for all functions/classes)
│   ├── integration/ (Database and API integration tests)
│   ├── playwright/ (End-to-end browser testing)
│   ├── fixtures/ (Test data and mock files)
│   └── bootstrap.php (Test environment setup)
├── css/ (minimal stylesheet)
├── js/ (Alpine.js only)
├── includes/
│   ├── config.php (main configuration file)
│   ├── database.php (database connection with auto-setup)
│   ├── functions.php (utilities)
│   └── auth.php (simple session auth)
└── .htaccess (clean URLs)
```

## Critical Features

### Content Management (Admin Interface)
- **TinyMCE Editor:** Enhanced with:
  - Pagebreak functionality for multi-page photobooks
  - Custom image upload plugin with dual fields (display + lightbox images)
  - WCAG AA compliant markup generation
- **No Image Processing:** Upload images as-is, no server-side resizing
- **URL Aliases:** User-defined, browser-friendly URLs with title-based defaults
- **File Attachments:** Document upload system with custom display names
- **Version Control System:**
  - Autosave every 30 seconds while editing
  - Manual save creates numbered versions
  - Restore to any previous version
  - Future: Visual diff comparison (document for later implementation)
- **Content Management Features:**
  - Draft/Published/Deleted status workflow
  - Adjustable publication dates
  - Soft delete with trash bin and restore functionality
  - Meta information management (keywords, descriptions)
- **Site Settings:** Admin interface to manage:
  - Site title and motto (HTML-enabled for basic formatting)
  - Header background image upload
  - Header color overlay (color picker with opacity control)
  - Header height configuration
  - Copyright notice for footer
- **Menu Management:** Intuitive drag-and-drop interface for:
  - Top menu items (hamburger menu contents)
  - Bottom menu items (footer links)
  - Add/edit/remove menu links
  - Reorder menu items by dragging
  - Enable/disable menu items
- **Content Ordering:** Drag-and-drop sorting interfaces for:
  - Article display order (affects homepage and /articles listing)
  - Photobook display order (affects homepage and /photobooks listing)
  - Real-time AJAX updates to sort_order field
  - Visual feedback during drag operations
- **User Management:** (Admin role only)
  - Add/edit/delete user accounts
  - Role assignment (admin vs editor permissions)
  - Activate/deactivate user accounts
  - User activity tracking (last login)
- **Profile Management:** (All users)
  - Change password functionality
  - Secure password confirmation
  - Current user profile editing
- **Accessibility:** Full WCAG AA compliance throughout admin interface
- **Modern UI/UX:** Clean, modern interface design with intuitive workflows

### Public Interface
- **Homepage Layout:**
  - 1200px max-width, centered layout
  - Two-column: 66% articles (left) + 33% photobooks (right)
  - Articles: teaser image + title/summary + "Read More" button
  - Photobooks: featured image + title + teaser + centered "Read More" button
- **Header:** 
  - Admin-configurable height and background image with color overlay
  - Site title (left, HTML-enabled) + motto (right, HTML-enabled)
  - Site title links to homepage
- **Navigation:** 
  - Hamburger menu (always visible) with graceful slide animations
  - Default links: Home (/), Articles (/articles), Photobooks (/photobooks) + admin-configured items
- **Listing Pages:**
  - /articles: Full article listing with same layout as homepage left column
  - /photobooks: Full photobook listing with same layout as homepage right column
  - Content ordered by admin-configured sort_order field
- **Footer:** Admin-configured copyright notice + bottom menu links
- **Articles:** Title → Featured Image → TinyMCE content display
- **Photobooks:** Title → Featured Image → Page navigation using TinyMCE pagebreaks
- **Responsive:** Mobile-first with column stacking, 4:3 teaser image optimization
- **Alpine.js:** Lightbox functionality, hamburger menu, smooth interactions, drag-drop menu management
- **History Management:** Browser back/forward button support with proper URL state tracking

### Authentication
- Simple session-based auth with role-based permissions
- Default admin user: username `admin`, password `130Bpm`
- **User Management Features:**
  - Add new users (admin role only)
  - Edit user details (admin role only)
  - Activate/deactivate users (admin role only)
  - Delete users (admin role only)
  - Role assignment: admin or editor
- **Password Management:**
  - Change password functionality for current user
  - Secure password hashing (PHP password_hash/password_verify)
  - Password confirmation required for changes

## URL Structure
```
/ (homepage)
/articles (article listing page)
/photobooks (photobook listing page)
/article/[alias] (individual articles)
/article/[alias]?page=[number] (for paginated articles if needed)
/photobook/[alias] (photobook viewer)
/photobook/[alias]?page=[number] (specific photobook pages)
/admin/ (admin dashboard)
/admin/trash/ (deleted content management)
/admin/sort-articles/ (drag-drop article sorting)
/admin/sort-photobooks/ (drag-drop photobook sorting)
/downloads/[filename] (file attachment downloads)
```

## Technical Requirements
- **Clean URLs:** Using .htaccess rewriting with format `/[content-type]/[alias]?page=[number]`
- **Configuration Management:** Centralized config.php for easy database and site settings
- **Auto-Setup:** Automatic database and table creation on first run
- **Security:** CSRF protection, file upload validation, session management, role-based permissions
- **Password Security:** PHP password_hash() and password_verify() for secure authentication
- **Performance:** File-based caching, lazy loading, minimal CSS/JS
- **Mobile-First:** Responsive design with hamburger menu always visible
- **4:3 Photos:** Optimized display for photographer's preferred aspect ratio
- **WCAG AA Compliance:** Full accessibility compliance throughout site
- **Browser History:** Implement pushState/popState for proper back button functionality
  - Photobook page navigation updates URL without page reload
  - Browser back/forward buttons work correctly
  - Maintain scroll position when navigating
- **Version Control:** Database-backed content versioning system
- **Autosave:** JavaScript-based autosave every 30 seconds during editing
- **Soft Delete:** Trash bin system for content recovery
- **Role-Based Access:** Admin vs Editor permission levels with appropriate UI restrictions

## Key Implementation Notes
- TinyMCE pagebreak functionality creates multi-page photobooks
- Custom image upload dialog for display + lightbox image pairs
- No image processing server-side (photographer handles sizing)
- Ultra-minimal design philosophy - photography must never be compromised
- Vanilla PHP for simplicity on shared hosting environment
- **History API Integration:** Use `history.pushState()` and `window.addEventListener('popstate')` for:
  - Photobook page navigation without full page reloads
  - Proper URL updates when navigating between photobook pages
  - Back button restores previous page state correctly
  - Forward button works as expected
  - Bookmark-friendly URLs for any photobook page
- **Drag-and-Drop Menu Management:** Use Alpine.js with Sortable.js for:
  - Intuitive drag-and-drop interface in admin
  - Real-time reordering of menu items
  - AJAX updates to save new menu order
  - Separate management for top (hamburger) and bottom (footer) menus
  - Visual feedback during drag operations
- **Header Color Overlay Management:** Admin interface with:
  - Color picker for overlay color selection
  - Opacity/transparency slider (0-100%)
  - Live preview of color overlay on header image
  - CSS implementation using rgba() or background-blend-mode
- **Advanced Content Management Features:**
  - **Autosave System:** JavaScript timer saves content every 30 seconds to content_versions table
  - **Version History:** Track all saves with restore capability to any previous version
  - **Trash Management:** Soft delete system with restore functionality
  - **File Attachment System:** Per-content file uploads with custom display names
  - **Custom TinyMCE Image Plugin:** Dual image fields (display + lightbox) in upload dialog
  - **Pagebreak Navigation:** Split content into pages using TinyMCE pagebreak plugin
  - **WCAG AA Implementation:** Proper ARIA labels, keyboard navigation, color contrast, semantic markup
- **Frontend Layout Implementation:**
  - **CSS Grid/Flexbox:** 1200px max-width, 66%/33% column layout
  - **Responsive Design:** Mobile-first with column stacking below tablet breakpoint
  - **Button Styling:** 2px border-radius, consistent minimalistic design
  - **Hamburger Menu Animation:** CSS transitions for smooth slide in/out
  - **HTML Processing:** Safe HTML rendering for site title/motto (`<br>`, `<strong>`, `<em>`)
- **Content Sorting System:**
  - **Drag-and-Drop Interface:** Sortable.js integration for content ordering
  - **AJAX Updates:** Real-time sort_order field updates without page refresh
  - **Visual Feedback:** Smooth drag animations and drop zone highlighting
  - **Separate Admin Pages:** Dedicated sorting interfaces for articles and photobooks
  - **Frontend Reflection:** Homepage and listing pages respect admin-defined sort order
- **User Management System:**
  - **Role-Based Permissions:** Admin (full access) vs Editor (content only) roles
  - **User CRUD Operations:** Add, edit, activate/deactivate, delete users (admin only)
  - **Secure Authentication:** PHP password_hash/verify with session management
  - **Password Management:** Self-service password change with confirmation
  - **Activity Tracking:** Last login timestamps and user status monitoring
  - **Access Control:** UI elements hidden/shown based on user role permissions
- **Auto-Setup & Configuration:**
  - **Centralized Config:** Single config.php file for all database and site settings
  - **Database Auto-Creation:** Automatically create database if missing
  - **Table Auto-Setup:** Create all required tables with optimized indexes
  - **Default Data Population:** Insert initial settings, admin user, and menu items
  - **Error Handling:** Graceful setup failure handling with clear error messages
  - **Setup Verification:** Validate successful database and table creation
- **Document Import & Conversion System:**
  - **Python Integration:** Custom Python script for document-to-HTML conversion
  - **Supported Formats:** ODF, DOC, DOCX, PDF to TinyMCE-compatible HTML
  - **Seamless Workflow:** Upload → Convert → Populate TinyMCE → Review → Publish
  - **Temporary File Management:** 24-hour automatic cleanup of uploaded documents
  - **AJAX Integration:** Real-time conversion without page refresh
  - **Error Handling:** Graceful conversion failure handling with user feedback
  - **Unit Testing:** Test document parsing, HTML conversion, file cleanup
  - **Integration Testing:** Test complete upload-to-editor workflow
  - **Playwright Testing:** End-to-end document import user experience
- **Advanced Security & Monitoring:**
  - **UUID Primary Keys:** All records use UUID (never exposed in URLs or frontend)
  - **File Upload Security:** MIME validation, extension whitelisting, content verification
  - **SQL Injection Prevention:** Prepared statements with detection and logging
  - **Input Sanitization:** Frontend and backend validation before database storage
  - **Session Security:** Secure cookies, regeneration, CSRF tokens, timeout enforcement
  - **Login History:** 90-day user activity tracking with automatic cleanup
  - **Environment-Based Logging:** 
    - Development: All events to files + errors/major events to browser console
    - Production: Errors only to files, no console output
  - **Log Rotation:** 5000 line limits with automatic rotation
  - **Debug Mode:** Console debugging and error display in development environment
- **Future Enhancement Documentation:**
  - **Visual Diff System:** Plan for side-by-side version comparison interface
  - **Content Change Tracking:** Database schema supports diff implementation
  - **Collaborative Editing:** Framework ready for multi-user editing features

Build this as a complete, functional photography portfolio CMS that prioritizes simplicity, performance, and photographic content above all else.

## Critical Development Requirements

### Test-Driven Development Approach
1. **Write Tests First:** Create unit tests before implementing functionality
2. **Small Iterations:** Develop in small, manageable code blocks (20-30 lines max)
3. **Test Every Step:** Run unit and regression tests after each code change
4. **Playwright Integration:** Test all new functionality with end-to-end browser automation
5. **Continuous Validation:** Ensure no existing functionality breaks with new changes

### Code Quality Standards
- **Meticulous Documentation:** Every function, class, and complex logic thoroughly commented
- **Clear Variable Names:** Self-documenting code with descriptive naming
- **Error Handling:** Comprehensive try-catch blocks with detailed logging
- **Security First:** Validate, sanitize, and secure all inputs and outputs
- **Performance Monitoring:** Profile and optimize database queries and file operations

### Testing Coverage Requirements
- **Unit Tests:** 95%+ code coverage for all PHP functions and classes
- **Integration Tests:** Complete workflow testing from input to database to output
- **Browser Tests:** Playwright automation for all user interactions and workflows
- **Regression Tests:** Full test suite execution after every code change
- **Documentation Tests:** Verify all setup and usage instructions work correctly

This ensures a robust, secure, and maintainable codebase that can scale with your dad's photography website needs while maintaining the ultra-minimal design philosophy.