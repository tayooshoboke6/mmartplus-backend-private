<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProductDeliveryTimeSeeder extends Seeder
{
    /**
     * Run the database seeds to update existing products with random delivery times.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting to update products with random delivery times...');
        
        // Delivery time options from the frontend dropdown
        $deliveryTimeOptions = [
            'Delivery in minutes',
            'Delivery in 24 hrs',
            'Delivery in 48 hrs',
            'Delivery in 2-4 business days'
        ];
        
        // Get all products without a delivery time set
        $products = Product::all();
        $updateCount = 0;
        
        // Begin transaction to improve performance with many updates
        DB::beginTransaction();
        
        try {
            foreach ($products as $product) {
                // Assign a random delivery time
                $randomIndex = array_rand($deliveryTimeOptions);
                $product->delivery_time = $deliveryTimeOptions[$randomIndex];
                $product->save();
                $updateCount++;
            }
            
            DB::commit();
            $this->command->info("Successfully updated {$updateCount} products with random delivery times.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to update products: ' . $e->getMessage());
        }
    }
}
