# Production Deployment Guide

## Single-Port Architecture

The Dalthaus.net CMS is designed as a **single application** that serves both public and admin areas through the same port. This mirrors standard web hosting where everything runs on ports 80 (HTTP) and 443 (HTTPS).

## URL Structure

In production, all URLs are served from the same domain:

- **Public Site**: `https://dalthaus.net/`
- **Admin Panel**: `https://dalthaus.net/admin/login.php`
- **Articles**: `https://dalthaus.net/articles`
- **Photobooks**: `https://dalthaus.net/photobooks`

## Development vs Production

### Development
```bash
# Single server instance on one port
php -S localhost:8000 router.php

# Both accessible through same port:
# - http://localhost:8000/           (public)
# - http://localhost:8000/admin/     (admin)
```

### Production
- Apache/Nginx serves everything on ports 80/443
- HTTPS is enforced via .htaccess redirect
- No separate ports or services needed

## Apache Configuration

For production Apache setup:

```apache
<VirtualHost *:80>
    ServerName dalthaus.net
    ServerAlias www.dalthaus.net
    DocumentRoot /var/www/dalthaus_net
    
    # Force HTTPS redirect
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{SERVER_NAME}/$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName dalthaus.net
    ServerAlias www.dalthaus.net
    DocumentRoot /var/www/dalthaus_net
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    <Directory /var/www/dalthaus_net>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Security Considerations

1. **HTTPS Enforcement**: The .htaccess file includes rules to force HTTPS in production
2. **Single Entry Point**: All requests route through the same application, improving security
3. **Admin Protection**: The `/admin/` directory requires authentication via PHP sessions
4. **No Port Exposure**: No additional ports need to be opened in firewall

## Deployment Steps

1. **Upload Files**
   ```bash
   # Upload all files to web root (e.g., /var/www/dalthaus_net)
   rsync -avz --exclude='node_modules' ./ user@server:/var/www/dalthaus_net/
   ```

2. **Set Permissions**
   ```bash
   chmod 755 /var/www/dalthaus_net
   chmod 755 /var/www/dalthaus_net/uploads
   chmod 755 /var/www/dalthaus_net/cache
   chmod 755 /var/www/dalthaus_net/logs
   chmod 755 /var/www/dalthaus_net/temp
   ```

3. **Configure Production Settings**
   Edit `includes/config.php`:
   ```php
   define('ENV', 'production');
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'production_db');
   define('DB_USER', 'production_user');
   define('DB_PASS', 'secure_password');
   ```

4. **Enable HTTPS Redirect**
   Uncomment in `.htaccess`:
   ```apache
   RewriteCond %{HTTPS} off
   RewriteCond %{HTTP_HOST} !^localhost
   RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
   ```

5. **Run Setup**
   ```bash
   php setup.php
   ```

6. **Test Access**
   - Visit `https://yourdomain.com/` - Should show public site
   - Visit `https://yourdomain.com/admin/login.php` - Should show admin login
   - Both running on standard HTTPS port 443

## Nginx Configuration (Alternative)

If using Nginx instead of Apache:

```nginx
server {
    listen 80;
    server_name dalthaus.net www.dalthaus.net;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name dalthaus.net www.dalthaus.net;
    
    root /var/www/dalthaus_net;
    index index.php;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Deny access to sensitive directories
    location ~ ^/(includes|logs|cache|temp|tests)/ {
        deny all;
    }
    
    # Clean URLs
    location / {
        try_files $uri $uri/ /index.php?route=$uri&$args;
    }
    
    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Deny access to .htaccess
    location ~ /\.ht {
        deny all;
    }
}
```

## Key Points

- ✅ **Single Application**: Admin and public are one cohesive system
- ✅ **Single Port**: Everything runs on standard web ports (80/443)
- ✅ **Shared Resources**: CSS, JS, and PHP includes are shared
- ✅ **Unified Sessions**: Same session handling for both areas
- ✅ **Consistent URLs**: Clean URL structure throughout
- ✅ **HTTPS Everywhere**: Automatic redirect to secure connection

This architecture simplifies deployment, maintenance, and security by treating the CMS as a single, integrated application rather than separate frontend/backend services.