<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);
        
        $viewOrderPermission = Permission::create(['name' => 'view orders']);
        $createOrderPermission = Permission::create(['name' => 'create orders']);
        $updateOrderPermission = Permission::create(['name' => 'update orders']);
        $viewAnyOrderPermission = Permission::create(['name' => 'view any orders']);
        $updateOrderStatusPermission = Permission::create(['name' => 'update order status']);
        
        $adminRole->givePermissionTo([
            $viewOrderPermission,
            $createOrderPermission,
            $updateOrderPermission,
            $viewAnyOrderPermission,
            $updateOrderStatusPermission
        ]);
        
        $customerRole->givePermissionTo([
            $viewOrderPermission,
            $createOrderPermission,
            $updateOrderPermission
        ]);
    }
    
    /** @test */
    public function guests_cannot_view_orders()
    {
        $response = $this->getJson('/api/orders');
        
        $response->assertStatus(401);
    }
    
    /** @test */
    public function customer_can_view_their_orders()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        Order::factory()->count(3)->create([
            'user_id' => $customer->id
        ]);
        
        $response = $this->getJson('/api/orders');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'order_number',
                            'total',
                            'status',
                            'payment_status',
                            'payment_method'
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
    public function customer_can_view_a_specific_order()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        $order = Order::factory()->create([
            'user_id' => $customer->id
        ]);
        
        $response = $this->getJson('/api/orders/' . $order->id);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'order_number',
                    'total',
                    'status',
                    'payment_status',
                    'payment_method',
                    'shipping_address',
                    'billing_address',
                    'items'
                ]
            ]);
    }
    
    /** @test */
    public function customer_cannot_view_others_orders()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        $otherCustomer = User::factory()->create();
        $orderOfOtherCustomer = Order::factory()->create([
            'user_id' => $otherCustomer->id
        ]);
        
        Sanctum::actingAs($customer);
        
        $response = $this->getJson('/api/orders/' . $orderOfOtherCustomer->id);
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function admin_can_view_all_orders()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        // Create orders for different users
        Order::factory()->count(5)->create();
        
        $response = $this->getJson('/api/admin/orders');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'order_number',
                            'total',
                            'status',
                            'payment_status',
                            'user' => [
                                'id',
                                'name',
                                'email'
                            ]
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
    public function customer_can_create_order()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        // Create products for the order
        $category = Category::factory()->create();
        $product1 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 50.00,
            'stock' => 10
        ]);
        
        $product2 = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 75.00,
            'stock' => 5
        ]);
        
        $response = $this->postJson('/api/orders', [
            'items' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 2
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 1
                ]
            ],
            'shipping_address' => [
                'name' => 'John Doe',
                'address' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'postal_code' => '12345',
                'country' => 'USA',
                'phone' => '555-1234'
            ],
            'billing_address' => [
                'name' => 'John Doe',
                'address' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'postal_code' => '12345',
                'country' => 'USA',
                'phone' => '555-1234'
            ],
            'payment_method' => 'credit_card',
            'notes' => 'Please deliver in the evening'
        ]);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'total',
                        'status',
                        'payment_status',
                        'payment_method',
                        'items'
                    ]
                ]
            ]);
            
        // Check if stock was reduced
        $this->assertEquals(8, $product1->fresh()->stock);
        $this->assertEquals(4, $product2->fresh()->stock);
        
        // Check if order total is correct (2 * 50 + 1 * 75 = 175)
        $this->assertEquals(175.00, Order::first()->total);
    }
    
    /** @test */
    public function admin_can_update_order_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $order = Order::factory()->create([
            'status' => 'pending'
        ]);
        
        $response = $this->patchJson('/api/admin/orders/' . $order->id . '/status', [
            'status' => 'processing'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => [
                    'id' => $order->id,
                    'status' => 'processing'
                ]
            ]);
            
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing'
        ]);
    }
    
    /** @test */
    public function customer_cannot_update_order_status()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'pending'
        ]);
        
        $response = $this->patchJson('/api/admin/orders/' . $order->id . '/status', [
            'status' => 'processing'
        ]);
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function customer_can_update_payment_info()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending'
        ]);
        
        $response = $this->patchJson('/api/orders/' . $order->id . '/payment', [
            'payment_status' => 'paid',
            'transaction_id' => 'PAY-1234567890'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Payment status updated successfully'
            ])
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.transaction_id', 'PAY-1234567890');
            
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'transaction_id' => 'PAY-1234567890',
            'payment_status' => 'paid'
        ]);
    }
    
    /** @test */
    public function customer_cannot_update_payment_info_for_others_orders()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        $otherCustomer = User::factory()->create();
        $orderOfOtherCustomer = Order::factory()->create([
            'user_id' => $otherCustomer->id
        ]);
        
        Sanctum::actingAs($customer);
        
        $response = $this->patchJson('/api/orders/' . $orderOfOtherCustomer->id . '/payment', [
            'payment_status' => 'paid',
            'transaction_id' => 'PAY-1234567890'
        ]);
        
        $response->assertStatus(403);
    }
}
