# Implementation Notes - Dalthaus.net CMS

## Recent Updates (Session Date: 2025-08-11)

This document details the significant updates made to the CMS, particularly around TinyMCE configuration, page break handling, and multi-page content navigation.

---

## 1. TinyMCE Editor Configuration

### License and Branding
All TinyMCE instances have been configured with:
- `license_key: 'gpl'` - GPL license mode
- `promotion: false` - Removes upgrade prompts
- `branding: false` - Removes TinyMCE branding

### Locations Updated
- `/admin/articles.php` (lines 249-316)
- `/admin/photobooks.php` (lines 227-314)  
- `/admin/import.php` (lines 127-196)

### Content Styling
TinyMCE editors now match front-end styles exactly:

#### Typography
- **Body**: Gelasio serif, 1.1rem size, 1.8 line-height
- **Headings**: Arimo sans-serif, 600 weight
- **Colors**: 
  - Primary: #2c3e50 (headers)
  - Links: #3498db (articles), #8e44ad (photobooks)

#### Page Breaks
- Visual indicator: Dashed line with "Page Break" label
- Keyboard shortcut: `Ctrl+Shift+P`
- Separator: `<!-- page -->`

### Allowed HTML Elements
Comprehensive `extended_valid_elements` configuration includes:
- Media: img, video, audio, iframe
- Semantic HTML5: article, section, header, footer, aside, nav
- Tables: Full table support with all elements
- Text formatting: All standard elements plus mark, cite, code, pre
- **Important**: H1 is NOT allowed (reserved for page titles)

---

## 2. Page Break Tracking System

### Database Schema Updates

#### New Columns Added
Both `content`, `articles`, and `photobooks` tables now have:
- `page_breaks` (JSON) - Stores array of page information
- `page_count` (INT) - Quick reference for total pages

#### Migration Files Created
- `/migrations/add_page_tracking.php` - For content table
- `/migrations/update_photobooks_page_tracking.php` - For photobooks table
- `/migrations/update_articles_page_tracking.php` - For articles table

### Page Tracker Module
**File**: `/includes/page_tracker.php`

#### Key Functions
1. **`extractPageInfo($content)`**
   - Splits content by `<!-- page -->`
   - Extracts page titles from first H2-H6 or paragraph
   - Returns array with page numbers, titles, and positions

2. **`updatePageTracking($pdo, $contentId, $body, $table)`**
   - Updates page_breaks and page_count in database
   - Works with both unified content table and legacy tables
   - Called automatically on content save

3. **`getPageInfo($pdo, $contentId, $table)`**
   - Retrieves stored page information
   - Auto-detects table if not specified

4. **`generatePageMenu()` / `generatePageBreadcrumb()`**
   - Helper functions for creating navigation UI

### Integration Points

#### Admin Side
Articles and photobooks automatically track pages on save:
```php
// In admin/articles.php after save
updatePageTracking($pdo, $id, $body);
```

#### Front-end
Both `public/article.php` and `public/photobook.php` support:
- Page selector dropdown with titles
- Previous/Next navigation buttons
- Page dots (visual indicators)
- URL hash navigation (#page-2, #page-3)
- Browser history support

---

## 3. Python Document Converter

**File**: `/scripts/converter.py`

### Major Updates

#### H1 to H2 Conversion
- Function: `convert_h1_to_h2()`
- All H1 tags automatically converted to H2
- H1 reserved for page titles only

#### Page Break Detection
Function: `process_page_breaks()` detects:
- Word page break styles
- Multiple horizontal rules (`<hr>`)
- 4+ consecutive `<br>` tags
- Section markers (`---`, `***`, `___`)
- Explicit text: `[Page Break]` or `Page Break`
- Form feed characters (`\f`) from PDFs

#### PDF Processing
- Automatically inserts `<!-- page -->` between PDF pages
- Preserves natural page boundaries

#### Allowed Elements List
- New `--list-elements` flag shows all allowed HTML
- Matches TinyMCE configuration exactly
- 50+ elements with attributes defined

### Usage Examples
```bash
# Convert with page breaks
python3 scripts/converter.py document.docx

# List allowed HTML elements
python3 scripts/converter.py --list-elements
```

---

## 4. Multi-Page Navigation Implementation

### Features
1. **Smart Page Titles**
   - Extracted from first heading (H2-H6)
   - Falls back to first 50 chars of paragraph
   - Default: "Page X" if no content

2. **Navigation Controls**
   - Dropdown selector with page titles
   - Previous/Next buttons
   - Page dots (up to 10 visible)
   - Keyboard navigation support

3. **URL Structure**
   - `/article/slug#page-2`
   - `/photobook/slug#page-3`
   - Browser back/forward support

### JavaScript Implementation
Core functions in both article.php and photobook.php:
- `navigatePage(direction)` - Previous/Next navigation
- `goToPage(pageNum)` - Direct page jump
- `loadPage(pageNum)` - Content loading with fade transition
- `updateHistory()` - Browser history management

---

## 5. Important Technical Details

### Table Structure Compatibility
The system works with both:
- **Unified content table** (new structure, used by admin)
- **Legacy tables** (articles, photobooks - used by front-end)

This dual compatibility ensures smooth operation during migration.

### Page Break Format
Always use: `<!-- page -->`
- This exact format (with spaces)
- HTML comment syntax
- Preserved by TinyMCE with `custom_elements: '~comment'`

### Performance Considerations
1. Page information is cached in database (no runtime parsing)
2. Content pages are loaded via JavaScript (no page refresh)
3. Indexes added on `page_count` for query optimization

---

## 6. Testing & Verification

### Test Multi-Page Article
Created test article with alias: `test-multipage-article`
- 4 pages with different headings
- Demonstrates all navigation features

### Verification Commands
```bash
# Check page tracking for photobooks
php -r "require 'includes/config.php'; require 'includes/database.php'; 
\$pdo = Database::getInstance(); 
\$result = \$pdo->query('SELECT id, title, page_count FROM photobooks WHERE page_count > 1')->fetchAll(); 
print_r(\$result);"

# View page break data
php -r "require 'includes/config.php'; require 'includes/database.php'; 
\$pdo = Database::getInstance(); 
\$result = \$pdo->query('SELECT page_breaks FROM articles WHERE alias = \"test-multipage-article\"')->fetch(); 
echo json_encode(json_decode(\$result['page_breaks']), JSON_PRETTY_PRINT);"
```

---

## 7. Future Considerations

### Pending Migrations
- Front-end still uses legacy tables (articles, photobooks)
- Admin uses unified content table
- Complete migration path needed

### Potential Enhancements
1. **Table of Contents Widget**
   - Use `generatePageMenu()` function
   - Could be sidebar or dropdown

2. **Page Analytics**
   - Track which pages users read
   - Time spent per page

3. **Print Optimization**
   - Option to print single page or all pages
   - Page break CSS for printing

4. **SEO Considerations**
   - Canonical URLs for multi-page content
   - Structured data for article pages

### Known Limitations
1. Maximum practical page limit: ~50 pages (UI considerations)
2. Page titles limited to 255 chars (JSON storage)
3. No nested page hierarchy (flat structure only)

---

## 8. File Reference Summary

### Core Files Modified
- `/includes/page_tracker.php` - Page tracking functions
- `/scripts/converter.py` - Document converter with page breaks
- `/public/article.php` - Multi-page article viewer
- `/public/photobook.php` - Multi-page photobook viewer
- `/admin/articles.php` - Article editor with page tracking
- `/admin/photobooks.php` - Photobook editor with page tracking

### Database Migrations
- `/migrations/add_page_tracking.php`
- `/migrations/update_photobooks_page_tracking.php`
- `/migrations/update_articles_page_tracking.php`

### Configuration Constants
No new constants added - all configuration inline or in database

---

## Quick Start for Next Session

1. **Check current state**:
   ```bash
   php -r "require 'includes/config.php'; require 'includes/database.php'; 
   \$pdo = Database::getInstance(); 
   echo 'Articles with pages: ' . \$pdo->query('SELECT COUNT(*) FROM articles WHERE page_count > 1')->fetchColumn() . PHP_EOL;
   echo 'Photobooks with pages: ' . \$pdo->query('SELECT COUNT(*) FROM photobooks WHERE page_count > 1')->fetchColumn();"
   ```

2. **Test the features**:
   - Visit `/article/test-multipage-article` to see multi-page navigation
   - Create new content with `<!-- page -->` markers in admin
   - Import document and check page break detection

3. **Key functions to remember**:
   - `updatePageTracking()` - Call after any content save
   - `getPageInfo()` - Retrieve page data for display
   - `extractPageInfo()` - Parse content for page breaks

---

*Document created: 2025-08-11*
*Last updated: 2025-08-11*