#!/bin/bash

# Script to fix CORS issues on M-Mart+ backend
# Run this script on your Digital Ocean server

echo "===== Fixing CORS Configuration for M-Mart+ Backend ====="

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    echo "Error: This script must be run from the root of your Laravel project."
    echo "Please navigate to your Laravel project directory and try again."
    exit 1
fi

# Backup the current cors.php file
echo "Creating backup of current CORS configuration..."
cp config/cors.php config/cors.php.backup
echo "Backup created at config/cors.php.backup"

# Create the new CORS configuration file
echo "Updating CORS configuration..."
cat > config/cors.php << 'EOF'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register', 'email/verify/*', 'forgot-password', 'reset-password'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'https://m-martplus.com',
        'https://www.m-martplus.com',
        'https://dev.m-martplus.com',
        'https://staging.m-martplus.com',
        'https://mmartplus-frontend.vercel.app',
        'https://mmartplus-fe.vercel.app',
        'https://*.vercel.app',  // This will allow all vercel.app subdomains
    ],

    'allowed_origins_patterns' => [
        // Add patterns if needed
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
EOF

echo "CORS configuration updated"

# Clear Laravel configuration cache
echo "Clearing Laravel configuration cache..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
echo "Cache cleared"

# Restart PHP-FPM (adjust service name if different)
echo "Restarting PHP service..."
if systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
elif systemctl is-active --quiet php8.0-fpm; then
    sudo systemctl restart php8.0-fpm
elif systemctl is-active --quiet php7.4-fpm; then
    sudo systemctl restart php7.4-fpm
else
    echo "Warning: Could not detect PHP-FPM service. Please manually restart your PHP service."
fi

# Restart Nginx
echo "Restarting Nginx..."
if systemctl is-active --quiet nginx; then
    sudo systemctl restart nginx
else
    echo "Warning: Nginx service not detected. Please manually restart your web server."
fi

echo "===== CORS Configuration Update Completed ====="
echo ""
echo "The following origins are now allowed:"
echo "- http://localhost:3000"
echo "- http://localhost:5173"
echo "- http://127.0.0.1:3000"
echo "- http://127.0.0.1:5173"
echo "- https://m-martplus.com"
echo "- https://www.m-martplus.com"
echo "- https://dev.m-martplus.com"
echo "- https://staging.m-martplus.com"
echo "- https://mmartplus-frontend.vercel.app"
echo "- https://mmartplus-fe.vercel.app"
echo "- https://*.vercel.app"
echo ""
echo "If you need to add more origins in the future, edit config/cors.php"
echo "then clear the config cache with: php artisan config:clear"
echo ""
echo "To verify if CORS is working, check the response headers in your browser's network tab"
echo "You should see: Access-Control-Allow-Origin: [your-origin]"
