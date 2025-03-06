<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            [
                'name' => 'Nigerian Foods',
                'description' => 'Traditional Nigerian food items and ingredients',
                'image_url' => 'categories/nigerian-foods.jpg',
                'color' => '#67A97D',
                'order' => 1,
            ],
            [
                'name' => 'Electronics',
                'description' => 'Phones, gadgets, and electronic appliances',
                'image_url' => 'categories/electronics.jpg',
                'color' => '#4A90E2',
                'order' => 2,
            ],
            [
                'name' => 'Fashion',
                'description' => 'Traditional and modern Nigerian fashion items',
                'image_url' => 'categories/fashion.jpg',
                'color' => '#F5A623',
                'order' => 3,
            ],
            [
                'name' => 'Home & Kitchen',
                'description' => 'Household items and kitchen essentials',
                'image_url' => 'categories/home-kitchen.jpg',
                'color' => '#D0021B',
                'order' => 4,
            ],
            [
                'name' => 'Health & Beauty',
                'description' => 'Personal care and beauty products',
                'image_url' => 'categories/health-beauty.jpg',
                'color' => '#9013FE',
                'order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                    'image_url' => $category['image_url'],
                    'color' => $category['color'],
                    'order' => $category['order'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Product categories created successfully!');
    }
}
