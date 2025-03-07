#!/bin/bash

# Script to update M-Mart+ backend and create test users
# Run this script on your Digital Ocean server

echo "===== Updating M-Mart+ Backend and Creating Test Users ====="

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "Error: This script must be run from the root of your Laravel project."
    echo "Please navigate to your Laravel project directory and try again."
    exit 1
fi

# Pull the latest code
echo "Pulling latest code..."
git pull

# Install any new dependencies
echo "Updating dependencies..."
composer install --no-interaction --no-dev --optimize-autoloader

# Run migrations (optional, only if your database schema has changed)
# echo "Running migrations..."
# php artisan migrate --force

# Register the seeder in DatabaseSeeder.php if it's not already registered
if ! grep -q "TestUsersSeeder" database/seeders/DatabaseSeeder.php; then
    echo "Adding TestUsersSeeder to DatabaseSeeder.php..."
    sed -i "/public function run/a \        \$this->call(TestUsersSeeder::class);" database/seeders/DatabaseSeeder.php
    echo "Added TestUsersSeeder to DatabaseSeeder.php"
else
    echo "TestUsersSeeder already registered in DatabaseSeeder.php"
fi

# Run the seeder to create test users
echo "Creating test users..."
php artisan db:seed --class=TestUsersSeeder

# Clear config cache to ensure everything works properly
echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "===== Process Completed ====="
echo "You should now have these test accounts:"
echo "Admin: admin@mmartplus.com / adminpassword"
echo "Customer: customer@mmartplus.com / customerpassword"
echo ""
echo "Please remember to change these passwords in production!"
