<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all categories
        $categories = Category::all();
        
        if ($categories->isEmpty()) {
            $this->command->info('No categories found. Please run CategorySeeder first.');
            return;
        }

        // Sample product data
        $products = [
            [
                'name' => 'Fresh Whole Milk',
                'description' => 'Fresh, creamy whole milk from grass-fed cows. Rich in calcium and protein.',
                'short_description' => 'Fresh whole milk from grass-fed cows',
                'price' => 12.00,
                'stock' => 24,
                'sku' => 'MILK-WH-1L',
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $categories->where('name', 'Dairy & Eggs')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Fresh+Milk']),
                'expiry_date' => now()->addDays(10)->format('Y-m-d'),
                'slug' => 'fresh-whole-milk',
            ],
            [
                'name' => 'Premium Basmati Rice',
                'description' => 'Premium quality basmati rice, known for its unique aroma and taste. Perfect for all your rice dishes.',
                'short_description' => 'Aromatic long grain rice',
                'price' => 75.00,
                'stock' => 12,
                'sku' => 'RICE-BS-5KG',
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $categories->where('name', 'Grains & Rice')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Basmati+Rice']),
                'expiry_date' => now()->addMonths(8)->format('Y-m-d'),
                'slug' => 'premium-basmati-rice',
            ],
            [
                'name' => 'Organic Fresh Tomatoes',
                'description' => 'Organically grown tomatoes, plump and juicy. Perfect for salads, sandwiches, and cooking.',
                'short_description' => 'Fresh organic tomatoes',
                'price' => 18.00,
                'stock' => 38,
                'sku' => 'VEG-TOM-1KG',
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $categories->where('name', 'Fresh Produce')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Organic+Tomatoes']),
                'expiry_date' => now()->addDays(5)->format('Y-m-d'),
                'slug' => 'organic-fresh-tomatoes',
            ],
            [
                'name' => 'Frozen Chicken Breast',
                'description' => 'Premium quality boneless chicken breast, perfect for grilling, baking, or stir-frying.',
                'short_description' => 'Boneless chicken breast',
                'price' => 55.00,
                'stock' => 45,
                'sku' => 'MEAT-CH-1KG',
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $categories->where('name', 'Meat & Seafood')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Chicken+Breast']),
                'expiry_date' => now()->addMonths(3)->format('Y-m-d'),
                'slug' => 'frozen-chicken-breast',
            ],
            [
                'name' => 'Premium Dish Soap',
                'description' => 'Powerful dish soap that cuts through grease and grime, leaving your dishes sparkling clean.',
                'short_description' => 'Grease-cutting dish soap',
                'price' => 9.50,
                'stock' => 0,
                'sku' => 'HOME-DS-500ML',
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $categories->where('name', 'Household Supplies')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Dish+Soap']),
                'expiry_date' => null,
                'slug' => 'premium-dish-soap',
            ],
            [
                'name' => 'Fresh Eggs (Crate of 30)',
                'description' => 'Farm-fresh eggs from free-range chickens. Rich in protein and perfect for breakfast or baking.',
                'short_description' => 'Free-range chicken eggs',
                'price' => 32.00,
                'stock' => 8,
                'sku' => 'EGGS-FR-30',
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $categories->where('name', 'Dairy & Eggs')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Fresh+Eggs']),
                'expiry_date' => now()->addDays(15)->format('Y-m-d'),
                'slug' => 'fresh-eggs-crate-of-30',
            ],
            [
                'name' => 'Local Honey (500g)',
                'description' => 'Pure, raw honey sourced from local beekeepers. Naturally sweet and full of nutrients.',
                'short_description' => 'Pure raw local honey',
                'price' => 45.00,
                'stock' => 32,
                'sku' => 'PANT-HN-500G',
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $categories->where('name', 'Pantry Items')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Local+Honey']),
                'expiry_date' => now()->addYears(1)->format('Y-m-d'),
                'slug' => 'local-honey-500g',
            ],
            [
                'name' => 'Laundry Detergent (2kg)',
                'description' => 'High-efficiency laundry detergent that removes tough stains while being gentle on fabrics.',
                'short_description' => 'Stain-removing laundry detergent',
                'price' => 35.00,
                'stock' => 0,
                'sku' => 'HOME-LD-2KG',
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $categories->where('name', 'Household Supplies')->first()->id ?? $categories->random()->id,
                'images' => json_encode(['https://via.placeholder.com/400x400?text=Laundry+Detergent']),
                'expiry_date' => null,
                'slug' => 'laundry-detergent-2kg',
            ],
        ];

        foreach ($products as $productData) {
            // Check if product already exists
            $existingProduct = Product::where('sku', $productData['sku'])->first();
            
            if (!$existingProduct) {
                $product = Product::create($productData);
                $this->command->info("Created product: {$product->name}");
            } else {
                $this->command->info("Product '{$productData['name']}' already exists. Skipping.");
            }
        }
    }
}
