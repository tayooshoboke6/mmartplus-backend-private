<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Category;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_belongs_to_a_category()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        
        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertEquals($category->id, $product->category->id);
    }
    
    /** @test */
    public function it_can_be_found_by_sku()
    {
        $product = Product::factory()->create(['sku' => 'TEST-SKU-123']);
        
        $foundProduct = Product::where('sku', 'TEST-SKU-123')->first();
        
        $this->assertNotNull($foundProduct);
        $this->assertEquals($product->id, $foundProduct->id);
    }
    
    /** @test */
    public function it_has_price_attribute_as_float()
    {
        $product = Product::factory()->create(['price' => 99.99]);
        
        $this->assertIsFloat($product->price);
        $this->assertEquals(99.99, $product->price);
    }
    
    /** @test */
    public function it_can_determine_if_in_stock()
    {
        $inStockProduct = Product::factory()->create(['stock' => 5]);
        $outOfStockProduct = Product::factory()->create(['stock' => 0]);
        
        $this->assertTrue($inStockProduct->inStock());
        $this->assertFalse($outOfStockProduct->inStock());
    }
    
    /** @test */
    public function it_can_reduce_stock()
    {
        $product = Product::factory()->create(['stock' => 10]);
        
        $product->reduceStock(3);
        
        $this->assertEquals(7, $product->stock);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7
        ]);
    }
    
    /** @test */
    public function it_can_restore_stock()
    {
        $product = Product::factory()->create(['stock' => 5]);
        
        $product->restoreStock(3);
        
        $this->assertEquals(8, $product->stock);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8
        ]);
    }
    
    /** @test */
    public function it_throws_exception_when_reducing_more_than_available_stock()
    {
        $this->expectException(\Exception::class);
        
        $product = Product::factory()->create(['stock' => 2]);
        $product->reduceStock(5);
    }
    
    /** @test */
    public function it_can_be_filtered_by_active_status()
    {
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);
        
        $activeProducts = Product::active()->get();
        
        $this->assertEquals(1, $activeProducts->count());
        $this->assertTrue($activeProducts->first()->is_active);
    }
}
