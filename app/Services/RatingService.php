<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class RatingService
{
    /**
     * The default mean rating used in Bayesian calculation.
     * 
     * @var float
     */
    protected $defaultMean = 3.5;
    
    /**
     * The confidence parameter (weight) for the prior.
     * Higher values make the system more conservative.
     * 
     * @var int
     */
    protected $confidenceWeight = 5;
    
    /**
     * Calculate Bayesian average for a product.
     * 
     * @param \App\Models\Product $product
     * @return float
     */
    public function calculateBayesianRating(Product $product)
    {
        // If no ratings, return 0
        if ($product->rating_count === 0) {
            return 0;
        }
        
        // Get global average from all products with at least one rating
        $globalAverage = $this->getGlobalAverageRating();
        
        // Calculate Bayesian average
        $bayesianRating = (($this->confidenceWeight * $globalAverage) + 
                          ($product->average_rating * $product->rating_count)) / 
                          ($this->confidenceWeight + $product->rating_count);
        
        return round($bayesianRating, 2);
    }
    
    /**
     * Calculate Bayesian average for all products.
     * 
     * @return void
     */
    public function recalculateAllProductRatings()
    {
        $products = Product::where('rating_count', '>', 0)->get();
        $globalAverage = $this->getGlobalAverageRating();
        
        foreach ($products as $product) {
            $bayesianRating = (($this->confidenceWeight * $globalAverage) + 
                              ($product->average_rating * $product->rating_count)) / 
                              ($this->confidenceWeight + $product->rating_count);
            
            $product->bayesian_rating = round($bayesianRating, 2);
            $product->save();
        }
    }
    
    /**
     * Get the global average rating across all products.
     * 
     * @return float
     */
    public function getGlobalAverageRating()
    {
        $result = DB::table('products')
            ->where('rating_count', '>', 0)
            ->selectRaw('SUM(average_rating * rating_count) as total_rating_sum, SUM(rating_count) as total_ratings')
            ->first();
            
        if ($result && $result->total_ratings > 0) {
            return $result->total_rating_sum / $result->total_ratings;
        }
        
        return $this->defaultMean;
    }
    
    /**
     * Update a product's ratings when a new rating is added,
     * updated, or deleted.
     * 
     * @param \App\Models\Product $product
     * @param int $oldRating
     * @param int $newRating
     * @param bool $isNew
     * @return void
     */
    public function updateProductRating(Product $product, $oldRating, $newRating, $isNew)
    {
        if ($isNew) {
            // Adding new rating
            $newCount = $product->rating_count + 1;
            $newAverage = (($product->average_rating * $product->rating_count) + $newRating) / $newCount;
            
            $product->rating_count = $newCount;
            $product->average_rating = $newAverage;
        } else if ($newRating > 0) {
            // Updating existing rating
            $newAverage = (($product->average_rating * $product->rating_count) - $oldRating + $newRating) / $product->rating_count;
            $product->average_rating = $newAverage;
        } else {
            // Removing a rating
            $newCount = $product->rating_count - 1;
            
            if ($newCount > 0) {
                $newAverage = (($product->average_rating * $product->rating_count) - $oldRating) / $newCount;
                $product->average_rating = $newAverage;
            } else {
                // If no ratings left, reset to default
                $product->average_rating = 0;
                $product->bayesian_rating = 0;
            }
            
            $product->rating_count = $newCount;
        }
        
        // Calculate and update the Bayesian rating
        $product->bayesian_rating = $this->calculateBayesianRating($product);
        
        $product->save();
    }
}
