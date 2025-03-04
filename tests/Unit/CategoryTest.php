<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_many_products()
    {
        // Create a category
        $category = Category::factory()->create();
        
        // Create products with that category
        Product::factory()->count(3)->create([
            'category_id' => $category->id
        ]);
        
        // Assert the category has the products
        $this->assertCount(3, $category->products);
        $this->assertInstanceOf(Product::class, $category->products->first());
    }

    /** @test */
    public function it_generates_slug_from_name()
    {
        $category = Category::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);
        
        $this->assertEquals('test-category', $category->slug);
    }

    /** @test */
    public function it_can_have_parent_category()
    {
        $parentCategory = Category::factory()->create();
        
        $childCategory = Category::factory()->create([
            'parent_id' => $parentCategory->id
        ]);
        
        $this->assertEquals($parentCategory->id, $childCategory->parent_id);
        $this->assertInstanceOf(Category::class, $childCategory->parent);
    }

    /** @test */
    public function it_can_have_child_categories()
    {
        $parentCategory = Category::factory()->create();
        
        Category::factory()->count(3)->create([
            'parent_id' => $parentCategory->id
        ]);
        
        $this->assertCount(3, $parentCategory->children);
        $this->assertInstanceOf(Category::class, $parentCategory->children->first());
    }
}
