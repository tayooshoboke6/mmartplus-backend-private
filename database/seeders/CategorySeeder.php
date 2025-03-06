<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Delete existing categories instead of truncate to avoid foreign key constraints
        Category::whereNotNull('id')->delete();
        
        // Main categories
        $mainCategories = [
            [
                'name' => 'Food & Groceries',
                'description' => 'All food and grocery items',
                'color' => '#4CAF50',
                'order' => 10,
            ],
            [
                'name' => 'Household Essentials & Cleaning',
                'description' => 'Household and cleaning products',
                'color' => '#2196F3',
                'order' => 20,
            ],
            [
                'name' => 'Kitchen & Home Needs',
                'description' => 'Kitchen and home products',
                'color' => '#FF9800',
                'order' => 30,
            ],
            [
                'name' => 'Baby & Family Care',
                'description' => 'Products for babies and family care',
                'color' => '#E91E63',
                'order' => 40,
            ],
            [
                'name' => 'Drinks & Alcohol',
                'description' => 'Beverages and alcoholic drinks',
                'color' => '#9C27B0',
                'order' => 50,
            ],
            [
                'name' => 'Office & General Supplies',
                'description' => 'Office supplies and general products',
                'color' => '#607D8B',
                'order' => 60,
            ],
        ];
        
        $createdMainCategories = [];
        
        // Create main categories
        foreach ($mainCategories as $category) {
            $category['slug'] = Str::slug($category['name']);
            $category['is_active'] = true;
            $createdMainCategories[] = Category::create($category);
        }
        
        // Create subcategories
        $foodSubcategories = [
            [
                'name' => 'Staples & Grains',
                'description' => 'Rice, Beans, Garri, Semovita, Wheat, Yam, etc.',
                'color' => '#4CAF50',
                'order' => 10,
                'parent_id' => $createdMainCategories[0]->id,
            ],
            [
                'name' => 'Cooking Essentials',
                'description' => 'Flour, Baking Needs, Oils, Spices, Seasonings, Tomato Paste, etc.',
                'color' => '#4CAF50',
                'order' => 20,
                'parent_id' => $createdMainCategories[0]->id,
            ],
            [
                'name' => 'Packaged & Frozen Foods',
                'description' => 'Noodles, Pasta, Canned Foods, Sardines, Frozen Chicken, Fish, etc.',
                'color' => '#4CAF50',
                'order' => 30,
                'parent_id' => $createdMainCategories[0]->id,
            ],
            [
                'name' => 'Snacks & Beverages',
                'description' => 'Biscuits, Chocolates, Juice, Soft Drinks, Tea, Coffee, Milo, etc.',
                'color' => '#4CAF50',
                'order' => 40,
                'parent_id' => $createdMainCategories[0]->id,
            ],
            [
                'name' => 'Dairy & Breakfast',
                'description' => 'Milk, Yogurt, Eggs, Cereals, Custard, etc.',
                'color' => '#4CAF50',
                'order' => 50,
                'parent_id' => $createdMainCategories[0]->id,
            ],
            [
                'name' => 'Fruits & Vegetables',
                'description' => 'Fresh & Frozen Produce',
                'color' => '#4CAF50',
                'order' => 60,
                'parent_id' => $createdMainCategories[0]->id,
            ],
        ];
        
        $householdSubcategories = [
            [
                'name' => 'Cleaning & Laundry',
                'description' => 'Detergents, Soaps, Bleach, Mopping Liquids, Air Fresheners, etc.',
                'color' => '#2196F3',
                'order' => 10,
                'parent_id' => $createdMainCategories[1]->id,
            ],
            [
                'name' => 'Toiletries & Personal Care',
                'description' => 'Toothpaste, Tissue, Sanitary Pads, Deodorants, Perfumes, etc.',
                'color' => '#2196F3',
                'order' => 20,
                'parent_id' => $createdMainCategories[1]->id,
            ],
        ];
        
        $allSubcategories = array_merge($foodSubcategories, $householdSubcategories);
        
        // Create all subcategories
        foreach ($allSubcategories as $category) {
            $category['slug'] = Str::slug($category['name']);
            $category['is_active'] = true;
            Category::create($category);
        }
    }
}
