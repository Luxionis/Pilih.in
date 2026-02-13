# 🌱 Pilah.in - Environmental Platform

**Production-Ready Environmental Waste Management Platform**

Pilah.in is an elegant, futuristic platform that empowers communities to combat plastic pollution through gamified waste management.

![Pilah.in Banner](assets/images/banner.png)

## ✨ Features

### Core Functionality
- 🔐 **Secure Authentication** - Multi-step registration with email verification
- 📊 **Dashboard** - Personalized impact tracking and analytics
- ♻️ **Waste Logging** - Track and categorize waste disposal
- 🎮 **Gamification** - Points, achievements, and leaderboards
- 🎁 **Rewards System** - Redeem points for vouchers and products
- 🗺️ **TPA/TPS Locator** - Find nearest waste disposal locations
- 🤖 **AI Assistant** - 24/7 environmental guidance
- 👥 **Community** - Social features and collaborative challenges
- 📅 **Events** - Environmental activities and workshops

### Design Philosophy
- **Elegant & Futuristic** - Clean, sophisticated interface
- **Warm Color Palette** - Earth tones with modern accents
- **Mobile-First** - Fully responsive design
- **Accessible** - WCAG 2.1 AA compliant
- **Performant** - <2 second page loads

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Backend**: PHP 8.1+, MySQL 8.0+
- **Libraries**: Chart.js, Leaflet Maps, Font Awesome 6
- **Server**: Apache/Nginx with SSL

## 📋 Requirements

### Server Requirements
- PHP 8.1 or higher
- MySQL 8.0 or MariaDB 10.6+
- Apache 2.4+ or Nginx 1.20+
- SSL Certificate (Let's Encrypt recommended)
- Minimum 2GB RAM
- 10GB disk space

### PHP Extensions Required
- mysqli
- pdo_mysql
- json
- mbstring
- openssl
- curl
- gd or imagick (for image processing)

## 🚀 Installation

### 1. Clone or Download
```bash
# Clone repository
git clone https://github.com/yourusername/pilahin.git

# Or download and extract ZIP
```

### 2. Database Setup
```bash
# Create database
mysql -u root -p

# Import schema
mysql -u root -p < database/schema.sql

# Or use phpMyAdmin to import schema.sql
```

### 3. Configure Database Connection
```bash
# Copy config template
cp config/database.example.php config/database.php

# Edit database credentials
nano config/database.php
```

```php
<?php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pilahin');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
?>
```

### 4. Set File Permissions
```bash
# Make uploads directory writable
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/waste_logs/
chmod 755 uploads/events/

# Protect config files
chmod 600 config/database.php
```

### 5. Configure Apache/Nginx

#### Apache (.htaccess)
```apache
# Enable rewrite engine
RewriteEngine On
RewriteBase /

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# API routing
RewriteRule ^api/(.*)$ api/$1 [L]

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### Nginx
```nginx
server {
    listen 80;
    server_name pilahin.com www.pilahin.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pilahin.com www.pilahin.com;
    
    root /var/www/pilahin;
    index index.html index.php;
    
    ssl_certificate /etc/letsencrypt/live/pilahin.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pilahin.com/privkey.pem;
    
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location /api {
        try_files $uri $uri/ /api/index.php?$args;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

### 6. SSL Certificate (Let's Encrypt)
```bash
# Install certbot
sudo apt install certbot python3-certbot-apache

# Get certificate
sudo certbot --apache -d pilahin.com -d www.pilahin.com

# Auto-renewal
sudo certbot renew --dry-run
```

## 📂 Project Structure

```
pilahin/
├── index.html              # Landing page
├── login.html              # Login page
├── register.html           # Registration page
├── dashboard.html          # User dashboard
├── assets/
│   ├── css/
│   │   ├── main.css       # Main stylesheet
│   │   ├── auth.css       # Authentication styles
│   │   └── dashboard.css  # Dashboard styles
│   ├── js/
│   │   ├── main.js        # Core functionality
│   │   ├── auth.js        # Authentication
│   │   ├── register.js    # Registration flow
│   │   └── dashboard.js   # Dashboard functionality
│   └── images/            # Images and icons
├── api/
│   ├── auth/
│   │   ├── login.php      # Login endpoint
│   │   ├── register.php   # Registration endpoint
│   │   └── logout.php     # Logout endpoint
│   ├── waste/             # Waste management endpoints
│   ├── rewards/           # Rewards endpoints
│   ├── events/            # Events endpoints
│   └── user/              # User profile endpoints
├── config/
│   ├── database.php       # Database configuration
│   └── constants.php      # App constants
├── database/
│   └── schema.sql         # Database schema
└── uploads/               # User uploads directory
```

## 🔧 Configuration

### Environment Variables
Create `.env` file in root:
```env
APP_NAME=Pilah.in
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pilahin.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=pilahin
DB_USERNAME=pilahin_user
DB_PASSWORD=your_secure_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@pilahin.com
MAIL_FROM_NAME=Pilah.in

SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
```

### Security Hardening
```php
// config/security.php
<?php
// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);

// Password hashing
define('PASSWORD_ALGO', PASSWORD_ARGON2ID);

// CSRF protection
define('CSRF_TOKEN_LENGTH', 32);

// Rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
?>
```

## 📊 Database Management

### Backup
```bash
# Full backup
mysqldump -u root -p pilahin > backup_$(date +%Y%m%d).sql

# Compressed backup
mysqldump -u root -p pilahin | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Restore
```bash
# Restore from backup
mysql -u root -p pilahin < backup_20240101.sql

# From compressed backup
gunzip < backup_20240101.sql.gz | mysql -u root -p pilahin
```

### Maintenance
```bash
# Optimize tables
mysqlcheck -o pilahin -u root -p

# Repair tables
mysqlcheck -r pilahin -u root -p

# Analyze tables
mysqlcheck -a pilahin -u root -p
```

## 🔒 Security Best Practices

1. **Always use HTTPS** in production
2. **Keep PHP and MySQL updated**
3. **Use prepared statements** for all database queries
4. **Implement rate limiting** for API endpoints
5. **Sanitize all user inputs**
6. **Set secure session cookies**
7. **Use strong passwords** for database users
8. **Regular security audits**
9. **Keep error logs** outside web root
10. **Implement CSRF protection**

## 🎯 Performance Optimization

### PHP Configuration
```ini
; php.ini optimizations
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 20M
post_max_size = 25M
opcache.enable = 1
opcache.memory_consumption = 128
```

### MySQL Optimization
```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_waste_user_date ON waste_logs(user_id, logged_at);
CREATE INDEX idx_points_user ON user_points(user_id, total_points);

-- Enable query cache
SET GLOBAL query_cache_size = 67108864;
SET GLOBAL query_cache_type = 1;
```

### Caching Strategy
- Use browser caching for static assets
- Implement Redis/Memcached for session storage
- Enable opcache for PHP
- Use CDN for images and static files

## 📱 Mobile App (PWA)

Add `manifest.json` for Progressive Web App:
```json
{
  "name": "Pilah.in",
  "short_name": "Pilah.in",
  "description": "Platform lingkungan untuk melawan polusi plastik",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#fefefe",
  "theme_color": "#1a3d2e",
  "icons": [
    {
      "src": "/assets/images/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/assets/images/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

## 🧪 Testing

### Manual Testing Checklist
- [ ] User registration and email verification
- [ ] Login with correct/incorrect credentials
- [ ] Password reset flow
- [ ] Waste logging functionality
- [ ] Points calculation
- [ ] Reward redemption
- [ ] Event registration
- [ ] Profile updates
- [ ] Mobile responsiveness
- [ ] Cross-browser compatibility

### Load Testing
```bash
# Using Apache Bench
ab -n 1000 -c 10 https://pilahin.com/

# Using wrk
wrk -t12 -c400 -d30s https://pilahin.com/
```

## 📈 Monitoring

### Log Files
```bash
# Apache logs
tail -f /var/log/apache2/error.log
tail -f /var/log/apache2/access.log

# PHP errors
tail -f /var/log/php/error.log

# MySQL logs
tail -f /var/log/mysql/error.log
```

### Performance Monitoring
- Google Analytics for user behavior
- New Relic/Datadog for application performance
- UptimeRobot for uptime monitoring
- Sentry for error tracking

## 🚧 Troubleshooting

### Common Issues

**Database Connection Failed**
```bash
# Check MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u pilahin_user -p pilahin
```

**404 Errors on API Routes**
```bash
# Check mod_rewrite is enabled (Apache)
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check .htaccess exists and is readable
```

**Session Issues**
```bash
# Check session directory permissions
ls -la /var/lib/php/sessions/

# Clear sessions
sudo rm /var/lib/php/sessions/*
```

## 📞 Support

- **Documentation**: https://docs.pilahin.com
- **Email**: support@pilahin.com
- **Issues**: https://github.com/yourusername/pilahin/issues

## 📄 License

This project is licensed under the MIT License - see LICENSE file for details.

## 🙏 Credits

- **Design**: Inspired by modern eco-friendly platforms
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Playfair Display, Inter)
- **Charts**: Chart.js
- **Maps**: Leaflet.js

## 🌟 Contributing

We welcome contributions! Please see CONTRIBUTING.md for details.

---

**Made with 💚 for a greener planet**
