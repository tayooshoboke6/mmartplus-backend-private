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

        // Map of category names to their IDs
        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[$category->name] = $category->id;
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
            [
                'name' => 'Jollof Rice Spice Mix',
                'description' => 'Premium authentic Jollof rice spice mix. Contains all the necessary spices to prepare the perfect Nigerian Jollof rice. Made with natural ingredients including dried pepper, thyme, curry powder, bay leaves, and other special spices. 250g package.',
                'short_description' => 'Complete authentic spice mix for perfect Jollof rice',
                'price' => 1500.00,
                'stock' => 100,
                'sku' => 'NIG-JOLLOF-01',
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $categoryIds['Nigerian Foods'] ?? $categories->random()->id,
                'images' => json_encode(['products/jollof-spice.jpg']),
                'expiry_date' => now()->addMonths(6)->format('Y-m-d'),
                'slug' => 'jollof-rice-spice-mix',
            ],
            [
                'name' => 'Nigerian Premium Honey',
                'description' => 'Pure natural honey sourced from Nigerian bee farms. No additives or preservatives. Great for sweetening tea, spread on bread, or used in traditional remedies. Rich in antioxidants and nutrients. 500ml glass jar.',
                'short_description' => 'Pure natural honey from Nigerian farms',
                'price' => 3500.00,
                'stock' => 50,
                'sku' => 'NIG-HONEY-01',
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $categoryIds['Nigerian Foods'] ?? $categories->random()->id,
                'images' => json_encode(['products/nigerian-honey.jpg']),
                'expiry_date' => now()->addYears(1)->format('Y-m-d'),
                'slug' => 'nigerian-premium-honey',
            ],
            [
                'name' => 'Solar Power Bank 20000mAh',
                'description' => 'High-capacity solar power bank with dual USB ports. Built-in solar panel for charging in emergency situations. Perfect for Nigeria\'s power challenges. Features fast charging technology, LED flashlight, and water-resistant casing. A must-have for every Nigerian home and office.',
                'short_description' => 'Solar-powered 20000mAh power bank with dual USB ports',
                'price' => 12000.00,
                'stock' => 30,
                'sku' => 'ELECT-SOLAR-01',
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $categoryIds['Electronics'] ?? $categories->random()->id,
                'images' => json_encode(['products/solar-powerbank.jpg']),
                'expiry_date' => null,
                'slug' => 'solar-power-bank-20000mah',
            ],
            [
                'name' => 'Adire Fabric Set',
                'description' => 'Traditional hand-dyed Adire fabric from Abeokuta. Each piece is unique with intricate patterns created using resist-dye techniques passed down through generations. Set includes 2 yards of premium quality cotton fabric in beautiful indigo pattern. Perfect for creating clothing, home decor, or as a conversation piece.',
                'short_description' => 'Hand-dyed traditional Nigerian fabric from Abeokuta',
                'price' => 8000.00,
                'stock' => 25,
                'sku' => 'FASH-ADIRE-01',
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $categoryIds['Fashion'] ?? $categories->random()->id,
                'images' => json_encode(['products/adire-fabric.jpg']),
                'expiry_date' => null,
                'slug' => 'adire-fabric-set',
            ],
            [
                'name' => 'Clay Cooking Pot (Ikoko)',
                'description' => 'Traditional Nigerian clay pot for cooking soups and stews. Handcrafted by skilled artisans using time-honored techniques. Enhances flavor and retains nutrients during cooking. The porous nature allows for slow, even cooking, making it perfect for preparing traditional Nigerian dishes like Efo Riro and Egusi soup. Medium size suitable for family meals.',
                'short_description' => 'Traditional clay pot for authentic Nigerian cooking',
                'price' => 4500.00,
                'stock' => 20,
                'sku' => 'HOME-IKOKO-01',
                'is_active' => true,
                'is_featured' => false,
                'category_id' => $categoryIds['Home & Kitchen'] ?? $categories->random()->id,
                'images' => json_encode(['products/clay-pot.jpg']),
                'expiry_date' => null,
                'slug' => 'clay-cooking-pot-ikoko',
            ],
            [
                'name' => 'African Black Soap',
                'description' => 'Authentic black soap made from plantain skin ash, cocoa pod, palm oil, and shea butter. Made using traditional West African methods. Great for all skin types, especially those with eczema, acne, or sensitive skin. The soap cleanses deeply while being gentle on the skin. A staple in Nigerian skincare routines. 250g bar.',
                'short_description' => 'Traditional plantain ash soap for skincare',
                'price' => 2000.00,
                'stock' => 75,
                'sku' => 'HEALTH-SOAP-01',
                'is_active' => true,
                'is_featured' => true,
                'category_id' => $categoryIds['Health & Beauty'] ?? $categories->random()->id,
                'images' => json_encode(['products/black-soap.jpg']),
                'expiry_date' => now()->addYears(2)->format('Y-m-d'),
                'slug' => 'african-black-soap',
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::where('sku', $productData['sku'])->first();
            
            if (!$product) {
                Product::create($productData);
            } else {
                $product->update($productData);
            }
        }

        $this->command->info('Products seeded successfully!');
    }
}
