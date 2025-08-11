# MAMP Pro Setup Instructions

## 1. Host Setup in MAMP Pro

1. Open MAMP Pro
2. Click "Hosts" → "+" to add a new host
3. Configure:
   - **Server Name:** `dalthaus.local` (or your preferred local domain)
   - **Document Root:** Point to `/Users/kevin/Desktop/dalthaus_net`
   - **PHP Version:** 8.3.14
   - **Enable:** Apache modules - `mod_rewrite`, `mod_headers`

## 2. Database Setup

1. Open phpMyAdmin (or Sequel Pro)
2. Create a new database called `dalthaus_cms`
3. Update `includes/config.php` with your MAMP MySQL credentials:
   ```php
   define('DB_HOST', 'localhost');  // or 127.0.0.1
   define('DB_NAME', 'dalthaus_cms');
   define('DB_USER', 'root');        // MAMP default
   define('DB_PASS', 'root');        // MAMP default
   ```

## 3. Fix Common MAMP Issues

### If you get 500 errors:

1. **Check .htaccess RewriteBase:**
   - If site is at root: `RewriteBase /`
   - If in subdirectory: `RewriteBase /dalthaus_net/`

2. **Run the test script:**
   Visit: `http://your-domain/test_mamp.php`
   This will show what's wrong.

3. **Check Apache error log:**
   MAMP Pro → Log → Apache Error Log

4. **Directory Permissions:**
   ```bash
   cd /Users/kevin/Desktop/dalthaus_net
   chmod 755 uploads cache logs temp
   ```

5. **Initialize Database:**
   ```bash
   php setup.php
   ```

## 4. Alternative: Use MAMP's Command Line

```bash
# Use MAMP's PHP
/Applications/MAMP/bin/php/php8.3.14/bin/php setup.php
```

## 5. Test URLs

After setup, test these URLs:
- Homepage: `http://dalthaus.local/`
- Admin: `http://dalthaus.local/admin/login`
- Test script: `http://dalthaus.local/test_mamp.php`

## 6. If Clean URLs Don't Work

Edit `.htaccess` and change:
```apache
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]
```

To:
```apache
RewriteRule ^(.*)$ /index.php?route=$1 [QSA,L]
```

## 7. Troubleshooting Checklist

- [ ] Apache mod_rewrite enabled in MAMP Pro
- [ ] .htaccess file is being read (test with gibberish - should cause 500 error)
- [ ] Database credentials are correct
- [ ] PHP 8.3+ is selected
- [ ] Document root points to correct folder
- [ ] Directories are writable (uploads, cache, logs, temp)
- [ ] No syntax errors in PHP files