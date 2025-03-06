<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);
        
        $viewDashboardPermission = Permission::create(['name' => 'view dashboard']);
        
        $adminRole->givePermissionTo([
            $viewDashboardPermission
        ]);
    }
    
    /** @test */
    public function customer_cannot_access_dashboard_stats()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        $response = $this->getJson('/api/admin/dashboard/stats');
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function admin_can_access_dashboard_stats()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        // Create some test data
        $category = Category::factory()->create();
        $products = Product::factory()->count(5)->create(['category_id' => $category->id]);
        
        // Create some customers
        $customers = User::factory()->count(3)->create();
        foreach ($customers as $customer) {
            $customer->assignRole('customer');
        }
        
        // Create some orders
        $this->createTestOrders($customers, $products);
        
        $response = $this->getJson('/api/admin/dashboard/stats');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_sales',
                    'total_orders',
                    'pending_orders',
                    'total_customers',
                    'recent_orders',
                    'popular_products',
                    'sales_by_category',
                    'daily_sales'
                ]
            ]);
        
        // Assert that we have the correct total number of orders
        $this->assertEquals(6, Order::count());
        $this->assertEquals(Order::count(), $response->json('data.total_orders'));
    }
    
    /** @test */
    public function admin_can_filter_dashboard_stats_by_date_range()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        // Create some test data
        $category = Category::factory()->create();
        $products = Product::factory()->count(5)->create(['category_id' => $category->id]);
        
        // Create some customers
        $customers = User::factory()->count(3)->create();
        foreach ($customers as $customer) {
            $customer->assignRole('customer');
        }
        
        // Create some orders with different dates
        $createdOrders = $this->createOrdersWithDifferentDates($customers, $products);
        
        // Filter for just the last week
        $startDate = Carbon::now()->subDays(7)->format('Y-m-d');
        $endDate = Carbon::now()->format('Y-m-d');
        
        $response = $this->getJson("/api/admin/dashboard/stats?start_date={$startDate}&end_date={$endDate}");
        
        $response->assertStatus(200);
        
        // We're looking for 2 orders in the last week because we created one 15 days ago
        // and two within the last 7 days
        $this->assertEquals(2, $response->json('data.total_orders'));
    }
    
    private function createTestOrders($customers, $products)
    {
        // Create 2 orders for each customer
        foreach ($customers as $customer) {
            // First order - completed
            $order1 = Order::factory()->create([
                'user_id' => $customer->id,
                'status' => 'completed',
                'payment_status' => 'paid',
                'total' => 0
            ]);
            
            // Add 2 random products to the order
            $product1 = $products->random();
            $product2 = $products->random();
            
            OrderItem::factory()->create([
                'order_id' => $order1->id,
                'product_id' => $product1->id,
                'product_name' => $product1->name,
                'price' => $product1->price,
                'quantity' => 2,
                'total' => $product1->price * 2
            ]);
            
            OrderItem::factory()->create([
                'order_id' => $order1->id,
                'product_id' => $product2->id,
                'product_name' => $product2->name,
                'price' => $product2->price,
                'quantity' => 1,
                'total' => $product2->price
            ]);
            
            // Calculate total
            $total1 = $order1->items->sum('total');
            $order1->update(['total' => $total1]);
            
            // Second order - pending
            $order2 = Order::factory()->create([
                'user_id' => $customer->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'total' => 0
            ]);
            
            // Add 1 random product to the order
            $product3 = $products->random();
            
            OrderItem::factory()->create([
                'order_id' => $order2->id,
                'product_id' => $product3->id,
                'product_name' => $product3->name,
                'price' => $product3->price,
                'quantity' => 1,
                'total' => $product3->price
            ]);
            
            // Calculate total
            $total2 = $order2->items->sum('total');
            $order2->update(['total' => $total2]);
        }
    }
    
    private function createOrdersWithDifferentDates($customers, $products)
    {
        $customer = $customers->first();
        
        // Order from 15 days ago
        $order1 = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'total' => 100,
            'created_at' => Carbon::now()->subDays(15),
            'updated_at' => Carbon::now()->subDays(15)
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order1->id,
            'product_id' => $products->first()->id,
            'product_name' => $products->first()->name,
            'price' => 100,
            'quantity' => 1,
            'total' => 100
        ]);
        
        // Order from 6 days ago (within the last week)
        $order2 = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'total' => 150,
            'created_at' => Carbon::now()->subDays(6),
            'updated_at' => Carbon::now()->subDays(6)
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order2->id,
            'product_id' => $products->first()->id,
            'product_name' => $products->first()->name,
            'price' => 150,
            'quantity' => 1,
            'total' => 150
        ]);
        
        // Order from today (within the last week)
        $order3 = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'total' => 200,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        
        OrderItem::factory()->create([
            'order_id' => $order3->id,
            'product_id' => $products->first()->id,
            'product_name' => $products->first()->name,
            'price' => 200,
            'quantity' => 1,
            'total' => 200
        ]);
        
        return [$order1, $order2, $order3];
    }
}
