# 🚀 Quick Access Guide for Dalthaus CMS

## The site is now running!

### Access URLs:

| Page | URL |
|------|-----|
| **Homepage** | http://127.0.0.1:5500/ |
| **Admin Login** | http://127.0.0.1:5500/admin/login |
| **Test Page** | http://127.0.0.1:5500/test.php |

### Login Credentials:
- **Username:** `admin`
- **Password:** `130Bpm`

### To Start the Server (if stopped):
```bash
php -S 127.0.0.1:5500
```

### Alternative with router (for clean URLs):
```bash
php -S 127.0.0.1:5500 router.php
```

### Common Issues & Fixes:

1. **If you see 404 errors:**
   - Use the router: `php -S 127.0.0.1:5500 router.php`

2. **If database connection fails:**
   - Make sure MySQL is running
   - Check credentials in `includes/config.php`

3. **If you prefer a different port:**
   - Change 5500 to any port you like: `php -S 127.0.0.1:8080`

### What's Working:
✅ Homepage with sample content
✅ Admin panel with full CMS features
✅ Article and Photobook management
✅ Autosave (every 30 seconds)
✅ Menu management with drag-drop
✅ File uploads
✅ Document import (Word/PDF)
✅ Version history
✅ Responsive design

### Quick Test:
1. Open http://127.0.0.1:5500/ in your browser
2. Click "Admin Login" or go to http://127.0.0.1:5500/admin/login
3. Login with admin/130Bpm
4. Create your first article!

---
**Server is currently running on port 5500**