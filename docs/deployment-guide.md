# M-Mart+ Backend Deployment Guide for DigitalOcean

This guide outlines the steps to deploy the M-Mart+ backend application to DigitalOcean.

## Prerequisites

1. DigitalOcean account
2. SSH key set up with DigitalOcean
3. Domain name configured with DigitalOcean DNS
4. Laravel project ready for deployment

## Step 1: Create a DigitalOcean Droplet

1. Log in to your DigitalOcean account
2. Click on "Create" > "Droplets"
3. Choose an image: Ubuntu 22.04 LTS
4. Choose a plan: Standard ($20/month recommended for production)
5. Choose a datacenter region closest to your users
6. Add your SSH key
7. Choose a hostname (e.g., `mmartplus-api`)
8. Click "Create Droplet"

## Step 2: Configure Your Server

SSH into your droplet:

```bash
ssh root@your_server_ip
```

Update and upgrade packages:

```bash
apt update && apt upgrade -y
```

Install required packages:

```bash
apt install -y nginx mysql-server php8.1-fpm php8.1-mbstring php8.1-xml php8.1-mysql php8.1-common php8.1-cli php8.1-curl php8.1-zip unzip git
```

## Step 3: Set Up MySQL

Secure MySQL installation:

```bash
mysql_secure_installation
```

Create a database and user:

```bash
mysql -u root -p
```

```sql
CREATE DATABASE mmartplus;
CREATE USER 'mmartuser'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON mmartplus.* TO 'mmartuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 4: Configure Nginx

Create a new Nginx server block:

```bash
nano /etc/nginx/sites-available/mmartplus
```

Add the following configuration:

```nginx
server {
    listen 80;
    server_name api.mmartplus.com;
    root /var/www/mmartplus/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
ln -s /etc/nginx/sites-available/mmartplus /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

## Step 5: Deploy Your Laravel Application

Create the application directory:

```bash
mkdir -p /var/www/mmartplus
```

Clone your repository:

```bash
git clone https://github.com/yourusername/mmartplus-backend.git /var/www/mmartplus
```

Install Composer dependencies:

```bash
cd /var/www/mmartplus
composer install --no-dev --optimize-autoloader
```

Set up environment file:

```bash
cp .env.example .env
nano .env
```

Update the following in your .env file:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.mmartplus.com

DB_DATABASE=mmartplus
DB_USERNAME=mmartuser
DB_PASSWORD=your_secure_password

QUEUE_CONNECTION=database
CACHE_DRIVER=file
SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=true
```

Generate application key:

```bash
php artisan key:generate
```

Run migrations and seeders:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Cache configuration:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set correct permissions:

```bash
chown -R www-data:www-data /var/www/mmartplus
chmod -R 755 /var/www/mmartplus
chmod -R 775 /var/www/mmartplus/storage /var/www/mmartplus/bootstrap/cache
```

## Step 6: Set Up SSL with Let's Encrypt

Install Certbot:

```bash
apt install -y certbot python3-certbot-nginx
```

Obtain SSL certificate:

```bash
certbot --nginx -d api.mmartplus.com
```

## Step 7: Set Up Queue Worker (Using Supervisor)

Install Supervisor:

```bash
apt install -y supervisor
```

Create a configuration file:

```bash
nano /etc/supervisor/conf.d/mmartplus-worker.conf
```

Add the following:

```
[program:mmartplus-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mmartplus/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/mmartplus/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start mmartplus-worker:*
```

## Step 8: Schedule Cron Job for Laravel Scheduler

Edit the crontab:

```bash
crontab -e
```

Add the following line:

```
* * * * * cd /var/www/mmartplus && php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check Laravel logs: `tail -f /var/www/mmartplus/storage/logs/laravel.log`
   - Check Nginx error logs: `tail -f /var/nginx/error.log`

2. **Permission Issues**
   - Ensure storage directory is writable: `chmod -R 775 /var/www/mmartplus/storage`

3. **Database Connection Issues**
   - Verify database credentials in .env
   - Check MySQL service: `systemctl status mysql`

## Maintenance and Updates

### Deploying Updates

```bash
cd /var/www/mmartplus
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart mmartplus-worker:*
```

### Backup Strategy

Set up automated backups:

```bash
apt install -y mysqldump
```

Create backup script:

```bash
nano /usr/local/bin/backup-mmartplus.sh
```

Add the following:

```bash
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/mmartplus"
MYSQL_USER="mmartuser"
MYSQL_PASSWORD="your_secure_password"
MYSQL_DATABASE="mmartplus"

# Ensure backup directory exists
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u$MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE > $BACKUP_DIR/db_backup_$TIMESTAMP.sql

# Compress database backup
gzip $BACKUP_DIR/db_backup_$TIMESTAMP.sql

# Files backup
tar -zcvf $BACKUP_DIR/files_backup_$TIMESTAMP.tar.gz /var/www/mmartplus

# Delete backups older than 7 days
find $BACKUP_DIR -type f -name "*.gz" -mtime +7 -delete
```

Make the script executable:

```bash
chmod +x /usr/local/bin/backup-mmartplus.sh
```

Add to crontab:

```bash
crontab -e
```

```
0 2 * * * /usr/local/bin/backup-mmartplus.sh
```
