<?php

// Bootstrap Laravel application
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductSection;
use Illuminate\Support\Facades\DB;

// Check if there are any products in the database
$productCount = DB::table('products')->count();
if ($productCount === 0) {
    echo "No products found in the database. Creating test product sections with placeholder product IDs.\n";
    $productIds = [1, 2, 3]; // Placeholder IDs
} else {
    // Get some real product IDs
    $productIds = DB::table('products')->limit(3)->pluck('id')->toArray();
    echo "Found " . count($productIds) . " products to use in test section.\n";
}

$section = new ProductSection();
$section->title = 'Featured Products';
$section->description = 'Our hand-picked featured products';
$section->type = 'featured';
$section->product_ids = $productIds;
$section->background_color = '#f0f0f0';
$section->text_color = '#333333';
$section->display_order = 1;
$section->active = true;
$section->save();

echo "Product Section created successfully!\n";
echo "ID: " . $section->id . "\n";
echo "Title: " . $section->title . "\n";
echo "Product IDs: " . json_encode($section->product_ids) . "\n";
