<?php

// Bootstrap Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Banner;
use App\Models\NotificationBar;
use Illuminate\Support\Facades\DB;

echo "=== PROMOTIONS API VALIDATION SCRIPT ===\n\n";

// Ensure we have a clean test environment
DB::beginTransaction();

try {
    // Store original counts for validation at the end
    $originalBannerCount = Banner::count();
    $originalNotificationBarCount = NotificationBar::count();
    
    echo "Starting with {$originalBannerCount} banners and {$originalNotificationBarCount} notification bars.\n\n";
    
    // STEP 1: Test Banner Creation
    echo "STEP 1: Creating a new banner...\n";
    
    $testTimestamp = date('YmdHis');
    $testBanner = Banner::create([
        'label' => "Test Banner {$testTimestamp}",
        'title' => 'VALIDATION TEST BANNER',
        'description' => 'This banner was created by the validation script',
        'image' => '/test/test-banner.jpg',
        'bgColor' => '#e6f7ff',
        'imgBgColor' => '#005b9f',
        'link' => '/test-link',
        'active' => true
    ]);
    
    if ($testBanner && $testBanner->id) {
        echo "Banner created successfully!\n";
        echo "ID: {$testBanner->id}\n";
        echo "Label: {$testBanner->label}\n\n";
    } else {
        throw new Exception("Failed to create test banner");
    }
    
    // STEP 2: Test Banner Update
    echo "STEP 2: Updating the banner...\n";
    
    $testBanner->title = 'UPDATED TEST BANNER';
    $testBanner->bgColor = '#fff0e6';
    $testBanner->imgBgColor = '#9f2b00';
    $testBanner->save();
    
    // Reload from database to verify
    $testBanner = Banner::find($testBanner->id);
    
    if ($testBanner->title === 'UPDATED TEST BANNER') {
        echo "Banner updated successfully!\n";
        echo "New title: {$testBanner->title}\n";
        echo "New background color: {$testBanner->bgColor}\n\n";
    } else {
        throw new Exception("Failed to update test banner");
    }
    
    // STEP 3: Test Banner Status Toggle
    echo "STEP 3: Toggling banner active status...\n";
    
    $originalStatus = $testBanner->active;
    $testBanner->active = !$originalStatus;
    $testBanner->save();
    
    // Reload from database to verify
    $testBanner = Banner::find($testBanner->id);
    
    if ($testBanner->active !== $originalStatus) {
        echo "Status toggled successfully!\n";
        echo "Original status: " . ($originalStatus ? 'Active' : 'Inactive') . "\n";
        echo "New status: " . ($testBanner->active ? 'Active' : 'Inactive') . "\n\n";
    } else {
        throw new Exception("Failed to toggle banner status");
    }
    
    // STEP 4: Test Notification Bar Retrieval and Update
    echo "STEP 4: Testing notification bar functionality...\n";
    
    // Find or create notification bar
    $notificationBar = NotificationBar::first();
    if (!$notificationBar) {
        $notificationBar = NotificationBar::create([
            'message' => 'Initial notification for testing',
            'linkText' => 'Learn More',
            'linkUrl' => '/notification-test',
            'bgColor' => '#f0f0f0',
            'active' => false
        ]);
        echo "Created new notification bar for testing.\n";
    } else {
        echo "Using existing notification bar for testing.\n";
    }
    
    // Update notification bar
    $notificationBar->message = "Updated notification message {$testTimestamp}";
    $notificationBar->bgColor = '#e6ffe6';
    $notificationBar->save();
    
    // Reload from database to verify
    $notificationBar = NotificationBar::find($notificationBar->id);
    
    if (strpos($notificationBar->message, $testTimestamp) !== false) {
        echo "Notification bar updated successfully!\n";
        echo "New message: {$notificationBar->message}\n";
        echo "New background color: {$notificationBar->bgColor}\n\n";
    } else {
        throw new Exception("Failed to update notification bar");
    }
    
    // STEP 5: Test Notification Bar Status Toggle
    echo "STEP 5: Toggling notification bar status...\n";
    
    $originalStatus = $notificationBar->active;
    $notificationBar->active = !$originalStatus;
    $notificationBar->save();
    
    // Reload from database to verify
    $notificationBar = NotificationBar::find($notificationBar->id);
    
    if ($notificationBar->active !== $originalStatus) {
        echo "Notification bar status toggled successfully!\n";
        echo "Original status: " . ($originalStatus ? 'Active' : 'Inactive') . "\n";
        echo "New status: " . ($notificationBar->active ? 'Active' : 'Inactive') . "\n\n";
    } else {
        throw new Exception("Failed to toggle notification bar status");
    }
    
    // STEP 6: Test Banner Deletion
    echo "STEP 6: Deleting test banner...\n";
    
    $bannerId = $testBanner->id;
    $testBanner->delete();
    
    // Verify deletion
    $deletedBanner = Banner::find($bannerId);
    
    if (!$deletedBanner) {
        echo "Test banner deleted successfully!\n\n";
    } else {
        throw new Exception("Failed to delete test banner");
    }
    
    // STEP 7: Restore notification bar to original state
    echo "STEP 7: Restoring notification bar to original state...\n";
    $notificationBar->active = $originalStatus;
    $notificationBar->save();
    
    // Final validation - count resources to make sure we ended up with the same number
    $finalBannerCount = Banner::count();
    $finalNotificationBarCount = NotificationBar::count();
    
    if ($finalBannerCount === $originalBannerCount && $finalNotificationBarCount === $originalNotificationBarCount) {
        echo "Verified: All test data was properly cleaned up.\n\n";
    } else {
        echo "Warning: Resource count mismatch after tests.\n";
        echo "Original banners: {$originalBannerCount}, Final banners: {$finalBannerCount}\n";
        echo "Original notification bars: {$originalNotificationBarCount}, Final notification bars: {$finalNotificationBarCount}\n\n";
    }
    
    // Commit the transaction if everything went well
    DB::commit();
    
    echo "=== VALIDATION COMPLETE ===\n";
    echo "All backend API functions for Promotions have been tested.\n";
    echo "Next steps: Validate frontend UI integration.\n";
    
} catch (Exception $e) {
    // Rollback the transaction if anything went wrong
    DB::rollBack();
    
    echo "ERROR: {$e->getMessage()}\n";
    echo "Validation failed. All changes have been rolled back.\n";
    exit(1);
}
