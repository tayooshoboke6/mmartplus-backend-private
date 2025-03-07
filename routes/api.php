<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StoreLocationController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ProductSectionController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductRatingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentication Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Email Verification Routes (Non-authenticated)
Route::post('/email/verification-code', [AuthController::class, 'sendEmailVerificationCode']);
Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
Route::post('/email/non-auth/send', [EmailVerificationController::class, 'sendNonAuth']);
Route::post('/email/non-auth/verify', [EmailVerificationController::class, 'verifyNonAuth']);

// Social Authentication Routes
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// Password Reset Routes
Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Public Routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/slug/{slug}', [ProductController::class, 'showBySlug']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// Store Location Routes (Public)
Route::get('/store-locations', [StoreLocationController::class, 'index']);
Route::get('/store-locations/pickup', [StoreLocationController::class, 'getPickupLocations']);
Route::get('/store-locations/nearby', [StoreLocationController::class, 'getNearby']);
Route::get('/store-locations/{id}', [StoreLocationController::class, 'show']);

// Public Promotion Routes
Route::get('/banners', [PromotionController::class, 'getActiveBanners']);
Route::get('/notification-bar', [PromotionController::class, 'getActiveNotificationBar']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // User Routes
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    
    // Phone verification routes
    Route::post('/phone/verify/send', [PhoneVerificationController::class, 'send']);
    Route::post('/phone/verify', [PhoneVerificationController::class, 'verify']);

    // Email verification routes
    Route::post('/email/verify/send', [EmailVerificationController::class, 'send']);
    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
    Route::get('/email/status', [EmailVerificationController::class, 'status']);

    // Product Ratings (Requires Authentication)
    Route::get('/products/{productId}/ratings', [ProductRatingController::class, 'getProductRatings']);
    Route::post('/products/{productId}/ratings', [ProductRatingController::class, 'rateProduct']);
    Route::delete('/ratings/{ratingId}', [ProductRatingController::class, 'deleteRating']);

    // Order Management Routes
    // Customer Order Routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}/payment', [OrderController::class, 'updatePayment']);
    
    // Voucher routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/vouchers', [VoucherController::class, 'index']);
        Route::post('/vouchers/apply', [VoucherController::class, 'apply']);
        
        // Admin voucher routes
        Route::middleware('can:manage vouchers')->group(function () {
            Route::post('/vouchers', [VoucherController::class, 'store']);
            Route::post('/vouchers/bulk', [VoucherController::class, 'generateBulk']);
            Route::post('/vouchers/schedule', [VoucherController::class, 'scheduleDistribution']);
            Route::get('/vouchers/{id}/stats', [VoucherController::class, 'getUsageStats']);
        });
    });

    // Admin Routes
    Route::middleware('admin')->group(function () {
        // Admin Dashboard
        Route::get('/admin/dashboard/stats', [DashboardController::class, 'getStats']);
        
        // Admin Order Routes
        Route::get('/admin/orders', [OrderController::class, 'adminOrders']);
        Route::patch('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
        
        // Admin Product Routes
        Route::post('/admin/products', [ProductController::class, 'store']);
        Route::put('/admin/products/{id}', [ProductController::class, 'update']);
        Route::delete('/admin/products/{id}', [ProductController::class, 'destroy']);
        
        // Admin Product Rating Route
        Route::put('/admin/products/{productId}/ratings', [ProductRatingController::class, 'adminAdjustRating']);
        
        // Admin Product Image Routes
        Route::post('/admin/products/{id}/images', [ProductImageController::class, 'upload']);
        Route::delete('/admin/products/{id}/images', [ProductImageController::class, 'remove']);
        Route::patch('/admin/products/{id}/images/primary', [ProductImageController::class, 'setPrimary']);
        
        // Admin Category Routes
        Route::get('/admin/categories', [CategoryController::class, 'index']);
        Route::post('/admin/categories', [CategoryController::class, 'store']);
        Route::put('/admin/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/admin/categories/{id}', [CategoryController::class, 'destroy']);
        
        // Admin Store Location Routes
        Route::post('/admin/store-locations', [StoreLocationController::class, 'store']);
        Route::put('/admin/store-locations/{id}', [StoreLocationController::class, 'update']);
        Route::delete('/admin/store-locations/{id}', [StoreLocationController::class, 'destroy']);
        Route::patch('/admin/store-locations/{id}/toggle-pickup', [StoreLocationController::class, 'togglePickupAvailability']);
        
        // Admin Promotion Routes
        Route::get('/admin/banners', [PromotionController::class, 'getBanners']);
        Route::post('/admin/banners', [PromotionController::class, 'storeBanner']);
        Route::put('/admin/banners/{id}', [PromotionController::class, 'updateBanner']);
        Route::delete('/admin/banners/{id}', [PromotionController::class, 'destroyBanner']);
        Route::put('/admin/banners/{id}/toggle-status', [PromotionController::class, 'toggleBannerStatus']);
        
        Route::get('/admin/notification-bar', [PromotionController::class, 'getNotificationBar']);
        Route::put('/admin/notification-bar', [PromotionController::class, 'updateNotificationBar']);
        Route::put('/admin/notification-bar/toggle-status', [PromotionController::class, 'toggleNotificationBarStatus']);
        
        // Admin User Management Routes
        Route::get('/admin/users', [UserController::class, 'index']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::get('/admin/users/{id}', [UserController::class, 'show']);
        Route::put('/admin/users/{id}', [UserController::class, 'update']);
        Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
        Route::get('/admin/roles', [UserController::class, 'getRoles']);
        
        // Admin Product Section Routes
        Route::apiResource('/admin/product-sections', ProductSectionController::class);
        Route::post('/admin/product-sections/reorder', [ProductSectionController::class, 'reorder']);
        Route::patch('/admin/product-sections/{id}/toggle', [ProductSectionController::class, 'toggleStatus']);
    });
});
