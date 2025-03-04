<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProductImageControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);
        
        $uploadImagePermission = Permission::create(['name' => 'upload product images']);
        $deleteImagePermission = Permission::create(['name' => 'delete product images']);
        
        $adminRole->givePermissionTo([
            $uploadImagePermission,
            $deleteImagePermission
        ]);
        
        // Set up fake storage disk
        Storage::fake('public');
    }
    
    /** @test */
    public function guest_cannot_upload_product_images()
    {
        $product = Product::factory()->create();
        
        $response = $this->postJson('/api/admin/products/' . $product->id . '/images', [
            'image' => UploadedFile::fake()->image('product.jpg')
        ]);
        
        $response->assertStatus(401);
    }
    
    /** @test */
    public function admin_can_upload_product_images()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $product = Product::factory()->create(['images' => null]);
        
        $response = $this->postJson('/api/admin/products/' . $product->id . '/images', [
            'image' => UploadedFile::fake()->image('product.jpg')
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Image uploaded successfully'
            ]);
            
        // Reload the product from the database
        $updatedProduct = Product::find($product->id);
        
        // Verify the product now has an image in its images array
        $this->assertNotNull($updatedProduct->images);
        $this->assertIsArray($updatedProduct->images);
        $this->assertCount(1, $updatedProduct->images);
        $this->assertTrue($updatedProduct->images[0]['is_primary']);
    }
    
    /** @test */
    public function customer_cannot_upload_product_images()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        $product = Product::factory()->create();
        
        $response = $this->postJson('/api/admin/products/' . $product->id . '/images', [
            'image' => UploadedFile::fake()->image('product.jpg')
        ]);
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function admin_can_delete_product_images()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        // Create a product with a fake image in its images array
        $imagePath = 'products/' . Str::random(40) . '.jpg';
        $product = Product::factory()->create([
            'images' => [
                [
                    'path' => $imagePath,
                    'url' => '/storage/' . $imagePath,
                    'is_primary' => true
                ]
            ]
        ]);
        
        // Create a fake file in storage
        Storage::fake('public');
        Storage::disk('public')->put($imagePath, 'fake image content');
        
        // Make the request to delete the image
        $response = $this->deleteJson('/api/admin/products/' . $product->id . '/images', [
            'image_index' => 0
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Image removed successfully'
            ]);
            
        // Reload the product and check that the image was removed
        $updatedProduct = Product::find($product->id);
        $this->assertEmpty($updatedProduct->images);
    }
    
    /** @test */
    public function admin_can_set_primary_image()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        // Create a product with two images
        $product = Product::factory()->create([
            'images' => [
                [
                    'path' => 'products/image1.jpg',
                    'url' => '/storage/products/image1.jpg',
                    'is_primary' => true
                ],
                [
                    'path' => 'products/image2.jpg',
                    'url' => '/storage/products/image2.jpg',
                    'is_primary' => false
                ]
            ]
        ]);
        
        // Set the second image as primary
        $response = $this->patchJson('/api/admin/products/' . $product->id . '/images/primary', [
            'image_index' => 1
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Primary image updated successfully'
            ]);
            
        // Reload the product and check that the primary image was updated
        $updatedProduct = Product::find($product->id);
        $this->assertFalse($updatedProduct->images[0]['is_primary']);
        $this->assertTrue($updatedProduct->images[1]['is_primary']);
    }
    
    /** @test */
    public function upload_validates_image_file()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $product = Product::factory()->create();
        
        // Test with non-image file
        $response = $this->postJson('/api/admin/products/' . $product->id . '/images', [
            'image' => UploadedFile::fake()->create('document.pdf', 100)
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }
    
    /** @test */
    public function upload_validates_max_file_size()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $product = Product::factory()->create();
        
        // Test with too large file (assume max is 2MB)
        $response = $this->postJson('/api/admin/products/' . $product->id . '/images', [
            'image' => UploadedFile::fake()->create('large_image.jpg', 3000) // 3MB
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }
}
