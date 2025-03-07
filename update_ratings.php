<?php

// This is a standalone script to recalculate all Bayesian ratings

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Services\RatingService;
use Illuminate\Support\Facades\DB;

echo "Starting Bayesian rating recalculation...\n";

try {
    // Get all products
    $products = Product::all();
    $ratingService = new RatingService();
    $count = 0;
    
    foreach ($products as $product) {
        $oldRating = $product->bayesian_rating ?? 0;
        
        // Calculate new Bayesian rating
        $bayesianRating = $ratingService->calculateBayesianRating($product);
        
        // Update product
        $product->bayesian_rating = $bayesianRating;
        $product->save();
        
        echo "Updated product ID: {$product->id}, Name: {$product->name}\n";
        echo "  Old rating: {$oldRating}, New Bayesian rating: {$bayesianRating}\n";
        $count++;
    }
    
    echo "\nSUCCESS: {$count} products updated with Bayesian ratings!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
