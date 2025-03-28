#!/bin/bash

# Script to update product API routes to be publicly accessible
# Run this script on your Digital Ocean server

# Colors for pretty output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting M-Mart+ API route update script...${NC}"

# Navigate to the Laravel project directory
# Update this path if your Laravel project is in a different location
cd /var/www/mmartplus || {
    echo -e "${RED}Failed to navigate to the Laravel project directory. Please check the path.${NC}"
    exit 1
}

# Backup the current routes file
echo -e "${YELLOW}Creating backup of the current routes file...${NC}"
cp routes/api.php routes/api.php.backup.$(date +%Y%m%d%H%M%S)

# Create a temporary file with updated route configuration
cat > routes/api.php.new << 'EOL'
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/verify/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Product routes - publicly accessible
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/slug/{slug}', [ProductController::class, 'showBySlug']);
    Route::get('/best-sellers', [ProductController::class, 'bestSellers']);
    Route::get('/new-arrivals', [ProductController::class, 'newArrivals']);
    Route::get('/related/{id}', [ProductController::class, 'relatedProducts'])->where('id', '[0-9]+');
});

// Category routes - publicly accessible
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+');
    Route::get('/slug/{slug}', [CategoryController::class, 'showBySlug']);
    Route::get('/{id}/products', [CategoryController::class, 'products'])->where('id', '[0-9]+');
});

// Routes that require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    // User auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification']);
    
    // User profile
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
    });
    
    // Addresses
    Route::apiResource('addresses', AddressController::class);
    
    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::put('/update/{id}', [CartController::class, 'update']);
        Route::delete('/remove/{id}', [CartController::class, 'remove']);
        Route::delete('/clear', [CartController::class, 'clear']);
    });
    
    // Wishlist
    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/add/{productId}', [WishlistController::class, 'add']);
        Route::delete('/remove/{productId}', [WishlistController::class, 'remove']);
    });
    
    // Reviews
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::put('/{id}', [ReviewController::class, 'update']);
        Route::delete('/{id}', [ReviewController::class, 'destroy']);
    });
    
    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store']);
        Route::put('/{id}/cancel', [OrderController::class, 'cancel']);
    });
    
    // Payments
    Route::prefix('payments')->group(function () {
        Route::post('/initiate', [PaymentController::class, 'initiate']);
        Route::post('/verify', [PaymentController::class, 'verify']);
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    });
    
    // Product routes that require authentication
    Route::prefix('products')->group(function () {
        Route::post('/', [ProductController::class, 'store'])->middleware('can:create products');
        Route::put('/{id}', [ProductController::class, 'update'])->middleware('can:edit products');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('can:delete products');
    });
    
    // Category routes that require authentication
    Route::prefix('categories')->group(function () {
        Route::post('/', [CategoryController::class, 'store'])->middleware('can:create categories');
        Route::put('/{id}', [CategoryController::class, 'update'])->middleware('can:edit categories');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->middleware('can:delete categories');
    });
});

// Admin routes (require admin role)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // User management
    Route::apiResource('users', UserController::class);
    
    // Promotions management
    Route::apiResource('promotions', PromotionController::class);
    
    // Orders management
    Route::prefix('orders')->group(function () {
        Route::get('/all', [OrderController::class, 'all']);
        Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
    });
    
    // Dashboard statistics
    Route::get('/dashboard', [UserController::class, 'dashboard']);
});
EOL

# Replace the current routes file with the updated one
echo -e "${YELLOW}Updating API routes configuration...${NC}"
mv routes/api.php.new routes/api.php

# Update file permissions
echo -e "${YELLOW}Setting proper file permissions...${NC}"
chmod 644 routes/api.php
chown www-data:www-data routes/api.php

# Clear Laravel cache
echo -e "${YELLOW}Clearing Laravel cache...${NC}"
php artisan cache:clear
php artisan route:clear
php artisan config:clear

# Restart the web server
echo -e "${YELLOW}Restarting web server...${NC}"
service nginx restart
service php8.2-fpm restart # Update with your PHP version if different

echo -e "${GREEN}API route update completed successfully!${NC}"
echo -e "${YELLOW}The product endpoints should now be publicly accessible without authentication.${NC}"
echo -e "${YELLOW}If you encounter any issues, you can restore the backup from:${NC} routes/api.php.backup.*"

exit 0

