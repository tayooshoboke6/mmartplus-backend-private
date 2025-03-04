<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);
        
        $viewProductPermission = Permission::create(['name' => 'view products']);
        $createProductPermission = Permission::create(['name' => 'create products']);
        $editProductPermission = Permission::create(['name' => 'edit products']);
        $deleteProductPermission = Permission::create(['name' => 'delete products']);
        
        $adminRole->givePermissionTo([
            $viewProductPermission,
            $createProductPermission,
            $editProductPermission,
            $deleteProductPermission
        ]);
        
        $customerRole->givePermissionTo([
            $viewProductPermission
        ]);
    }
    
    /** @test */
    public function guests_can_view_products()
    {
        $product = Product::factory()->create();
        
        $response = $this->getJson('/api/products');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'price',
                            'category_id',
                            'sku',
                            'stock',
                            'is_active'
                        ]
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total'
                ]
            ]);
    }
    
    /** @test */
    public function guests_can_view_a_single_product()
    {
        $product = Product::factory()->create();
        
        $response = $this->getJson('/api/products/' . $product->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'category_id',
                    'sku',
                    'stock',
                    'is_active',
                    'category' => [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }
    
    /** @test */
    public function guests_cannot_create_products()
    {
        $category = Category::factory()->create();
        
        $response = $this->postJson('/api/admin/products', [
            'name' => 'New Product',
            'description' => 'Product description',
            'price' => 99.99,
            'category_id' => $category->id,
            'sku' => 'SKU-TEST-123',
            'stock' => 10,
            'is_active' => true
        ]);
        
        $response->assertStatus(401);
    }
    
    /** @test */
    public function admin_can_create_products()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $category = Category::factory()->create();
        
        $response = $this->postJson('/api/admin/products', [
            'name' => 'New Product',
            'description' => 'Product description',
            'price' => 19.99,
            'sale_price' => 14.99,
            'category_id' => $category->id,
            'sku' => 'PRD-001',
            'stock' => 10,
            'is_active' => true
        ]);
        
        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Product created successfully'
            ])
            ->assertJsonPath('data.name', 'New Product')
            ->assertJsonPath('data.description', 'Product description')
            ->assertJsonPath('data.category_id', $category->id)
            ->assertJsonPath('data.is_active', true);
            
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
        ]);
    }
    
    /** @test */
    public function admin_can_update_products()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        
        $response = $this->putJson('/api/admin/products/' . $product->id, [
            'name' => 'Updated Product',
            'description' => 'Updated description',
            'price' => 29.99,
            'sale_price' => 24.99,
            'category_id' => $category->id,
            'sku' => 'PRD-001-UPD',
            'stock' => 20,
            'is_active' => true
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Product updated successfully'
            ])
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Updated Product')
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.is_active', true);
            
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
        ]);
    }
    
    /** @test */
    public function admin_can_delete_products()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $product = Product::factory()->create();
        
        $response = $this->deleteJson('/api/admin/products/' . $product->id);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
            
        $this->assertDatabaseMissing('products', [
            'id' => $product->id
        ]);
    }
    
    /** @test */
    public function customer_cannot_create_products()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        $category = Category::factory()->create();
        
        $response = $this->postJson('/api/admin/products', [
            'name' => 'New Product',
            'description' => 'Product description',
            'price' => 19.99,
            'sale_price' => 14.99,
            'category_id' => $category->id,
            'sku' => 'PRD-001',
            'stock' => 10,
            'is_active' => true
        ]);
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function validates_required_fields_when_creating_product()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $response = $this->postJson('/api/admin/products', [
            // Missing required fields
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'category_id']);
    }
}
