<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'stock' => $this->faker->numberBetween(0, 100),
            'category_id' => function () {
                return Category::factory()->create()->id;
            },
            'sku' => $this->faker->unique()->ean8(),
            'is_active' => true,
            'images' => null, // Default to null for images
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the product is out of stock.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function outOfStock()
    {
        return $this->state(function (array $attributes) {
            return [
                'stock' => 0,
            ];
        });
    }
    
    /**
     * Indicate that the product has images.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withImages()
    {
        return $this->state(function (array $attributes) {
            return [
                'images' => [
                    [
                        'path' => 'products/sample-1.jpg',
                        'url' => '/storage/products/sample-1.jpg',
                        'is_primary' => true
                    ],
                    [
                        'path' => 'products/sample-2.jpg',
                        'url' => '/storage/products/sample-2.jpg',
                        'is_primary' => false
                    ]
                ],
            ];
        });
    }
}
