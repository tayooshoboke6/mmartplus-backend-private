<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DefaultProductRatingsSeeder extends Seeder
{
    /**
     * Set all existing products to have a default 4.0 star rating.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Setting default 4.0 star rating for all products...');
        
        // Get all products
        $products = Product::all();
        $updateCount = 0;
        
        // Begin transaction for better performance
        DB::beginTransaction();
        
        try {
            foreach ($products as $product) {
                // Set default rating values if they don't already have ratings
                if ($product->rating_count == 0) {
                    $product->average_rating = 4.0;
                    $product->save();
                    $updateCount++;
                }
            }
            
            DB::commit();
            $this->command->info("Successfully set default 4.0 star rating for {$updateCount} products.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to set default ratings: ' . $e->getMessage());
        }
    }
}
