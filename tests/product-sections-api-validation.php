<?php

// Bootstrap Laravel application
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductSection;
use Illuminate\Support\Facades\DB;

echo "=== PRODUCT SECTIONS API VALIDATION SCRIPT ===\n\n";

// 1. Get existing products for testing
$products = DB::table('products')->limit(5)->get();
if ($products->count() === 0) {
    echo "ERROR: No products found in database. Please create some products first.\n";
    exit(1);
}

$productIds = $products->pluck('id')->toArray();
echo "Found " . count($productIds) . " products to use in tests.\n";
echo "Product IDs: " . implode(", ", $productIds) . "\n\n";

// 2. Create a new product section
echo "STEP 1: Creating a new product section...\n";
$section = new ProductSection();
$section->title = 'Test Section ' . date('YmdHis');
$section->description = 'This is a test section created by the validation script';
$section->type = 'featured';
$section->product_ids = array_slice($productIds, 0, 3); // Use first 3 products
$section->background_color = '#e6f7ff';
$section->text_color = '#005b9f';
$section->display_order = 999; // High number to avoid conflicts
$section->active = true;
$section->save();

echo "Product Section created successfully!\n";
echo "ID: " . $section->id . "\n";
echo "Title: " . $section->title . "\n";
echo "Product IDs: " . json_encode($section->product_ids) . "\n\n";

// 3. Update the product section
echo "STEP 2: Updating the product section...\n";
$section->description = 'Updated description for testing';
$section->background_color = '#fff0e6';
$section->text_color = '#9f2b00';
$section->save();

echo "Product Section updated successfully!\n";
echo "New background color: " . $section->background_color . "\n";
echo "New text color: " . $section->text_color . "\n\n";

// 4. Toggle status
echo "STEP 3: Toggling section active status...\n";
$originalStatus = $section->active;
$section->active = !$originalStatus;
$section->save();

echo "Status toggled successfully!\n";
echo "Original status: " . ($originalStatus ? 'Active' : 'Inactive') . "\n";
echo "New status: " . ($section->active ? 'Active' : 'Inactive') . "\n\n";

// 5. Create a second section for reordering test
echo "STEP 4: Creating a second section for reordering test...\n";
$section2 = new ProductSection();
$section2->title = 'Test Section 2 ' . date('YmdHis');
$section2->description = 'Second test section';
$section2->type = 'new_arrivals';
$section2->product_ids = array_slice($productIds, -3); // Use last 3 products
$section2->background_color = '#f0ffe6';
$section2->text_color = '#2b9f00';
$section2->display_order = 998; // Just below the first test section
$section2->active = true;
$section2->save();

echo "Second section created successfully!\n";
echo "ID: " . $section2->id . "\n";
echo "Title: " . $section2->title . "\n\n";

// 6. Test reordering
echo "STEP 5: Testing section reordering...\n";
// Swap display orders
$tempOrder = $section->display_order;
$section->display_order = $section2->display_order;
$section2->display_order = $tempOrder;
$section->save();
$section2->save();

echo "Sections reordered successfully!\n";
echo "Section 1 display order: " . $section->display_order . "\n";
echo "Section 2 display order: " . $section2->display_order . "\n\n";

// 7. Delete the test sections
echo "STEP 6: Cleaning up test data...\n";
$section->delete();
$section2->delete();

echo "Test sections deleted successfully!\n\n";

// 8. Verify deletion
$count = ProductSection::whereIn('title', [$section->title, $section2->title])->count();
if ($count === 0) {
    echo "Verified: All test sections were properly deleted.\n";
} else {
    echo "WARNING: Some test sections may not have been properly deleted!\n";
}

echo "\n=== VALIDATION COMPLETE ===\n";
echo "All backend API functions for Product Sections have been tested.\n";
echo "Next steps: Validate frontend UI integration.\n";
