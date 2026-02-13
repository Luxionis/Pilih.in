# 🚀 Pilah.in - Production Deployment Guide

Complete step-by-step guide to deploy Pilah.in to production server.

## 📋 Pre-Deployment Checklist

### Domain & Hosting
- [ ] Domain name registered (e.g., pilahin.com)
- [ ] VPS/Cloud server provisioned (recommended: DigitalOcean, AWS, or Vultr)
- [ ] DNS records configured
- [ ] Server meets minimum requirements:
  - Ubuntu 20.04 LTS or newer
  - 2GB RAM minimum (4GB recommended)
  - 20GB SSD storage
  - PHP 8.1+
  - MySQL 8.0+

### Required Accounts
- [ ] Email service (Gmail, SendGrid, or Mailgun)
- [ ] Cloudflare account (optional, for CDN)
- [ ] Google Maps API key (for location services)

---

## 🔧 Step 1: Server Setup

### 1.1 Connect to Server
```bash
ssh root@your_server_ip
```

### 1.2 Update System
```bash
apt update && apt upgrade -y
```

### 1.3 Install Required Software
```bash
# Install Apache, PHP, MySQL
apt install -y apache2 php8.1 php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl \
php8.1-gd php8.1-zip php8.1-bcmath mysql-server

# Install additional tools
apt install -y git curl unzip certbot python3-certbot-apache

# Enable Apache modules
a2enmod rewrite ssl headers
systemctl restart apache2
```

### 1.4 Secure MySQL
```bash
mysql_secure_installation

# Follow prompts:
# - Set root password: YES
# - Remove anonymous users: YES
# - Disallow root login remotely: YES
# - Remove test database: YES
# - Reload privilege tables: YES
```

---

## 📦 Step 2: Upload Files

### 2.1 Create Web Directory
```bash
mkdir -p /var/www/pilahin
cd /var/www/pilahin
```

### 2.2 Upload Files (Option A: Git)
```bash
# If using Git
git clone https://github.com/yourusername/pilahin.git .
```

### 2.2 Upload Files (Option B: FTP/SFTP)
```bash
# Use FileZilla or similar
# Upload to: /var/www/pilahin/
```

### 2.3 Set Permissions
```bash
# Set ownership
chown -R www-data:www-data /var/www/pilahin

# Set directory permissions
find /var/www/pilahin -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/pilahin -type f -exec chmod 644 {} \;

# Make upload directories writable
chmod 777 /var/www/pilahin/uploads
chmod 777 /var/www/pilahin/uploads/profiles
chmod 777 /var/www/pilahin/uploads/waste_logs
chmod 777 /var/www/pilahin/uploads/events
```

---

## 🗄️ Step 3: Database Setup

### 3.1 Create Database
```bash
mysql -u root -p
```

```sql
-- Create database
CREATE DATABASE pilahin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create database user
CREATE USER 'pilahin_user'@'localhost' IDENTIFIED BY 'YourSecurePassword123!';

-- Grant privileges
GRANT ALL PRIVILEGES ON pilahin.* TO 'pilahin_user'@'localhost';
FLUSH PRIVILEGES;

-- Exit MySQL
EXIT;
```

### 3.2 Import Schema
```bash
mysql -u pilahin_user -p pilahin < /var/www/pilahin/database/schema.sql
```

### 3.3 Verify Tables
```bash
mysql -u pilahin_user -p pilahin -e "SHOW TABLES;"
```

---

## ⚙️ Step 4: Configure Application

### 4.1 Create Database Config
```bash
nano /var/www/pilahin/config/database.php
```

```php
<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'pilahin');
define('DB_USER', 'pilahin_user');
define('DB_PASS', 'YourSecurePassword123!');
define('DB_CHARSET', 'utf8mb4');

// PDO Connection
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Database connection failed. Please try again later.");
}
?>
```

### 4.2 Create Constants Config
```bash
nano /var/www/pilahin/config/constants.php
```

```php
<?php
// Application Constants
define('APP_NAME', 'Pilah.in');
define('APP_URL', 'https://pilahin.com');
define('APP_ENV', 'production');
define('APP_DEBUG', false);

// Security
define('SESSION_LIFETIME', 7200); // 2 hours
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);

// File Upload
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Points System
define('POINTS_PER_KG', 10);
define('REFERRAL_BONUS', 500);
define('FIRST_LOG_BONUS', 100);

// Email
define('MAIL_FROM', 'noreply@pilahin.com');
define('MAIL_FROM_NAME', 'Pilah.in');

// API Keys (add your keys)
define('GOOGLE_MAPS_API_KEY', 'your_google_maps_api_key');
?>
```

### 4.3 Secure Config Files
```bash
chmod 600 /var/www/pilahin/config/*.php
```

---

## 🌐 Step 5: Apache Configuration

### 5.1 Create Virtual Host
```bash
nano /etc/apache2/sites-available/pilahin.conf
```

```apache
<VirtualHost *:80>
    ServerName pilahin.com
    ServerAlias www.pilahin.com
    ServerAdmin admin@pilahin.com
    
    DocumentRoot /var/www/pilahin
    
    <Directory /var/www/pilahin>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/pilahin_error.log
    CustomLog ${APACHE_LOG_DIR}/pilahin_access.log combined
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
```

### 5.2 Create .htaccess
```bash
nano /var/www/pilahin/.htaccess
```

```apache
# Enable Rewrite Engine
RewriteEngine On
RewriteBase /

# Force HTTPS (will be uncommented after SSL)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove www (optional)
RewriteCond %{HTTP_HOST} ^www\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]

# API Routing
RewriteRule ^api/(.*)$ api/$1 [L,QSA]

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

<FilesMatch "\.(sql|md|env)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/font-woff "access plus 1 year"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/css text/javascript application/javascript
</IfModule>
```

### 5.3 Enable Site
```bash
# Disable default site
a2dissite 000-default

# Enable Pilah.in site
a2ensite pilahin

# Test configuration
apache2ctl configtest

# Reload Apache
systemctl reload apache2
```

---

## 🔒 Step 6: SSL Certificate

### 6.1 Install SSL with Let's Encrypt
```bash
certbot --apache -d pilahin.com -d www.pilahin.com

# Follow prompts:
# - Enter email address
# - Agree to terms
# - Choose to redirect HTTP to HTTPS: Yes
```

### 6.2 Test Auto-Renewal
```bash
certbot renew --dry-run
```

### 6.3 Verify SSL
```bash
# Visit: https://www.ssllabs.com/ssltest/
# Test your domain for SSL configuration
```

---

## 🔥 Step 7: Firewall Configuration

### 7.1 Setup UFW
```bash
# Install UFW
apt install ufw

# Allow SSH
ufw allow OpenSSH

# Allow HTTP and HTTPS
ufw allow 'Apache Full'

# Enable firewall
ufw enable

# Check status
ufw status
```

---

## 📧 Step 8: Email Configuration

### 8.1 Configure PHP Mail (Using Gmail)
```bash
nano /etc/php/8.1/apache2/php.ini
```

Find and update:
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = noreply@pilahin.com
sendmail_path = /usr/sbin/sendmail -t -i
```

### 8.2 Test Email
Create test file: `/var/www/pilahin/test_email.php`
```php
<?php
$to = "your_email@example.com";
$subject = "Test Email from Pilah.in";
$message = "This is a test email.";
$headers = "From: noreply@pilahin.com";

if(mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Email failed!";
}
?>
```

Visit: `https://pilahin.com/test_email.php`

---

## 🔍 Step 9: Testing

### 9.1 Test Website
```bash
# Check homepage
curl -I https://pilahin.com

# Check SSL
curl -I https://pilahin.com | grep "HTTP"
```

### 9.2 Test Database Connection
Create: `/var/www/pilahin/test_db.php`
```php
<?php
require_once 'config/database.php';
echo "Database connected successfully!";
?>
```

### 9.3 Manual Testing
- [ ] Visit https://pilahin.com
- [ ] Test registration
- [ ] Test login
- [ ] Test all pages load correctly
- [ ] Check mobile responsiveness
- [ ] Test forms submission

---

## 📊 Step 10: Monitoring & Logs

### 10.1 Setup Log Rotation
```bash
nano /etc/logrotate.d/pilahin
```

```
/var/www/pilahin/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 10.2 Monitor Logs
```bash
# Watch Apache errors
tail -f /var/log/apache2/pilahin_error.log

# Watch access logs
tail -f /var/log/apache2/pilahin_access.log

# Watch MySQL logs
tail -f /var/log/mysql/error.log
```

---

## 🎯 Step 11: Performance Optimization

### 11.1 Enable OPcache
```bash
nano /etc/php/8.1/apache2/php.ini
```

```ini
[opcache]
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

### 11.2 MySQL Optimization
```bash
nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

```ini
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_size = 64M
query_cache_type = 1
```

### 11.3 Restart Services
```bash
systemctl restart apache2
systemctl restart mysql
```

---

## 🔐 Step 12: Security Hardening

### 12.1 Disable Directory Listing
Already done in `.htaccess` with: `Options -Indexes`

### 12.2 Hide PHP Version
```bash
nano /etc/php/8.1/apache2/php.ini
```

```ini
expose_php = Off
```

### 12.3 Secure MySQL
```sql
mysql -u root -p

DELETE FROM mysql.user WHERE User='';
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
```

### 12.4 Setup Fail2Ban
```bash
apt install fail2ban

# Configure
nano /etc/fail2ban/jail.local
```

```ini
[apache-auth]
enabled = true
port = http,https
filter = apache-auth
logpath = /var/log/apache2/pilahin_error.log
maxretry = 3
bantime = 3600
```

```bash
systemctl restart fail2ban
```

---

## 📱 Step 13: PWA Setup

### 13.1 Create Service Worker
Already included in project files.

### 13.2 Add Web App Manifest
Already included in project files.

### 13.3 Test PWA
- Visit: https://web.dev/measure/
- Enter your URL
- Check PWA score

---

## 🚀 Step 14: Go Live!

### 14.1 Final Checks
- [ ] SSL certificate working
- [ ] All pages accessible
- [ ] Forms working
- [ ] Database connections stable
- [ ] Email sending working
- [ ] Performance optimized
- [ ] Security hardened
- [ ] Backups configured

### 14.2 Remove Test Files
```bash
rm /var/www/pilahin/test_db.php
rm /var/www/pilahin/test_email.php
```

### 14.3 Set Production Mode
```bash
nano /var/www/pilahin/config/constants.php
```

```php
define('APP_DEBUG', false);
define('APP_ENV', 'production');
```

---

## 🔄 Step 15: Backup Strategy

### 15.1 Database Backup Script
```bash
nano /usr/local/bin/backup_pilahin.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/pilahin"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u pilahin_user -p'YourSecurePassword123!' pilahin | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/pilahin

# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
chmod +x /usr/local/bin/backup_pilahin.sh
```

### 15.2 Schedule Backups
```bash
crontab -e
```

Add:
```
# Daily backup at 2 AM
0 2 * * * /usr/local/bin/backup_pilahin.sh >> /var/log/pilahin_backup.log 2>&1
```

---

## 🎉 Congratulations!

Your Pilah.in platform is now live and production-ready!

### Post-Launch Checklist
- [ ] Monitor error logs daily
- [ ] Check performance metrics
- [ ] Review security logs
- [ ] Test backup restoration
- [ ] Update documentation
- [ ] Train administrators
- [ ] Prepare support team

### Useful Commands
```bash
# Check service status
systemctl status apache2
systemctl status mysql

# Restart services
systemctl restart apache2
systemctl restart mysql

# View logs
tail -f /var/log/apache2/pilahin_error.log

# Check disk space
df -h

# Check memory usage
free -h
```

---

**Need Help?**
- Documentation: https://docs.pilahin.com
- Support: support@pilahin.com
- Community: https://community.pilahin.com

**Made with 💚 for a greener planet**
