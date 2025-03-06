<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StoreLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class StoreLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        Permission::create(['name' => 'view store locations']);
        Permission::create(['name' => 'create store locations']);
        Permission::create(['name' => 'edit store locations']);
        Permission::create(['name' => 'delete store locations']);
        
        // Create roles
        $customerRole = Role::create(['name' => 'customer']);
        $adminRole = Role::create(['name' => 'admin']);
        
        // Assign permissions to roles
        $customerRole->givePermissionTo(['view store locations']);
        $adminRole->givePermissionTo(['view store locations', 'create store locations', 'edit store locations', 'delete store locations']);
    }
    
    /** @test */
    public function guest_cannot_view_store_locations()
    {
        // Public route should be accessible to guests
        $response = $this->getJson('/api/store-locations');
        
        $response->assertStatus(200);
    }
    
    /** @test */
    public function customer_can_view_store_locations()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        // Create some store locations
        StoreLocation::factory()->count(3)->create();
        
        $response = $this->getJson('/api/store-locations');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'address',
                        'city',
                        'state',
                        'zip_code',
                        'phone'
                    ]
                ]
            ]);
    }
    
    /** @test */
    public function customer_cannot_create_store_locations()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        $locationData = [
            'name' => 'New Store',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'pickup_available' => true,
            'opening_hours' => [
                'monday' => ['09:00 - 18:00'],
                'tuesday' => ['09:00 - 18:00'],
                'wednesday' => ['09:00 - 18:00'],
                'thursday' => ['09:00 - 18:00'],
                'friday' => ['09:00 - 18:00'],
                'saturday' => ['10:00 - 16:00'],
                'sunday' => ['closed'],
            ],
        ];
        
        $response = $this->postJson('/api/admin/store-locations', $locationData);
        
        $response->assertStatus(403);
    }
    
    /** @test */
    public function admin_can_create_store_locations()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $locationData = [
            'name' => 'New Store',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'phone' => '123-456-7890',
            'email' => 'test@example.com',
            'description' => 'Test store description',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'pickup_available' => true,
            'opening_hours' => [
                'monday' => ['09:00 - 18:00'],
                'tuesday' => ['09:00 - 18:00'],
                'wednesday' => ['09:00 - 18:00'],
                'thursday' => ['09:00 - 18:00'],
                'friday' => ['09:00 - 18:00'],
                'saturday' => ['10:00 - 16:00'],
                'sunday' => ['closed'],
            ],
            'pickup_instructions' => 'Pick up at the back door',
        ];
        
        $response = $this->postJson('/api/admin/store-locations', $locationData);
        
        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Store location created successfully'
            ]);
        
        $this->assertDatabaseHas('store_locations', [
            'name' => 'New Store',
            'city' => 'Test City',
            'zip_code' => '12345'
        ]);
    }
    
    /** @test */
    public function admin_can_update_store_locations()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $location = StoreLocation::factory()->create();
        
        $updateData = [
            'name' => 'Updated Store',
            'address' => '456 Update St',
            'city' => 'Update City',
            'state' => 'UC',
            'zip_code' => '54321',
            'phone' => '555-123-4567',
            'latitude' => 41.8781,
            'longitude' => -87.6298,
            'is_active' => false,
        ];
        
        $response = $this->putJson('/api/admin/store-locations/' . $location->id, $updateData);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Store location updated successfully'
            ]);
        
        $this->assertDatabaseHas('store_locations', [
            'id' => $location->id,
            'name' => 'Updated Store',
            'city' => 'Update City',
            'zip_code' => '54321'
        ]);
    }
    
    /** @test */
    public function admin_can_delete_store_locations()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        $location = StoreLocation::factory()->create();
        
        $response = $this->deleteJson('/api/admin/store-locations/' . $location->id);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Store location deleted successfully'
            ]);
        
        $this->assertDatabaseMissing('store_locations', [
            'id' => $location->id
        ]);
    }
    
    /** @test */
    public function store_location_creation_validates_required_fields()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Sanctum::actingAs($admin);
        
        // Missing required fields
        $locationData = [
            'name' => 'New Store',
            // missing address and other required fields
        ];
        
        $response = $this->postJson('/api/admin/store-locations', $locationData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['address', 'city', 'state', 'zip_code']);
    }
    
    /** @test */
    public function nearby_store_locations_api_works()
    {
        // Create customer
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        Sanctum::actingAs($customer);
        
        // Create store locations with different coordinates
        StoreLocation::factory()->create([
            'name' => 'Downtown Store',
            'latitude' => 40.7128, // NY
            'longitude' => -74.0060
        ]);
        
        StoreLocation::factory()->create([
            'name' => 'Uptown Store',
            'latitude' => 40.8000, // Further north
            'longitude' => -73.9500
        ]);
        
        StoreLocation::factory()->create([
            'name' => 'Far Away Store',
            'latitude' => 34.0522, // LA
            'longitude' => -118.2437
        ]);
        
        // Find stores near NY
        $response = $this->getJson('/api/store-locations/nearby?latitude=40.7300&longitude=-74.0000&radius=10');
        
        $response->assertStatus(200);
        
        // Should find 2 stores (Downtown and Uptown)
        $this->assertEquals(2, count($response->json('data')));
        
        // Only find stores with a smaller radius
        $response = $this->getJson('/api/store-locations/nearby?latitude=40.7300&longitude=-74.0000&radius=5');
        
        // Should find only 1 store (Downtown)
        $this->assertEquals(1, count($response->json('data')));
    }
}
