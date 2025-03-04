<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
        
        // Create permissions
        Permission::create(['name' => 'view dashboard']);
        
        // Assign permissions to admin role
        $adminRole->givePermissionTo('view dashboard');
    }
    
    /** @test */
    public function admin_can_access_dashboard_stats()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_sales',
                    'order_count',
                    'orders_by_status',
                    'new_customers',
                    'popular_products',
                    'recent_orders',
                    'low_stock_products',
                    'sales_by_day',
                    'date_range'
                ]
            ]);
    }
    
    /** @test */
    public function customer_cannot_access_dashboard_stats()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        $token = $customer->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(403);
    }
    
    /** @test */
    public function dashboard_stats_respects_date_filter()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $startDate = now()->subDays(10)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/admin/dashboard/stats?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        
        $this->assertEquals($startDate, $response->json('data.date_range.start_date'));
        $this->assertEquals($endDate, $response->json('data.date_range.end_date'));
    }
    
    /** @test */
    public function unauthenticated_user_cannot_access_dashboard_stats()
    {
        $response = $this->getJson('/api/admin/dashboard/stats');
        
        $response->assertStatus(401);
    }
}
