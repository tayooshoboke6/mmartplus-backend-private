#!/bin/bash

# Update package lists
apt update && apt upgrade -y

# Install required packages
apt install -y nginx mysql-server php8.1-fpm php8.1-mbstring php8.1-xml php8.1-mysql php8.1-common php8.1-cli php8.1-curl php8.1-zip unzip git

# Install Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
php -r "unlink('composer-setup.php');"

# Secure MySQL installation
mysql_secure_installation

# Create application directory
mkdir -p /var/www/mmartplus

# Clone the repository
git clone https://github.com/tayooshoboke6/mmartplus-backend-private.git /var/www/mmartplus

# Set proper permissions
chown -R www-data:www-data /var/www/mmartplus
chmod -R 755 /var/www/mmartplus
chmod -R 775 /var/www/mmartplus/storage /var/www/mmartplus/bootstrap/cache
