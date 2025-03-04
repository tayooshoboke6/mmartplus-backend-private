<?php

namespace Tests\Feature;

use App\Models\StoreLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class StoreLocationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }
    
    /** @test */
    public function anyone_can_view_store_locations()
    {
        StoreLocation::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'pickup_available' => true
        ]);
        
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
                        'zip_code'
                    ]
                ]
            ]);
    }
    
    /** @test */
    public function anyone_can_view_pickup_available_locations()
    {
        // Create pickup available store
        StoreLocation::create([
            'name' => 'Pickup Store',
            'address' => '123 Pickup St',
            'city' => 'Pickup City',
            'state' => 'PC',
            'zip_code' => '12345',
            'pickup_available' => true
        ]);
        
        // Create store without pickup
        StoreLocation::create([
            'name' => 'No Pickup Store',
            'address' => '456 No Pickup St',
            'city' => 'No Pickup City',
            'state' => 'NP',
            'zip_code' => '67890',
            'pickup_available' => false
        ]);
        
        $response = $this->getJson('/api/store-locations/pickup');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Pickup Store', $data[0]['name']);
    }
    
    /** @test */
    public function anyone_can_get_single_store_location()
    {
        $store = StoreLocation::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'phone' => '555-1234',
            'email' => 'test@store.com',
            'pickup_available' => true
        ]);
        
        $response = $this->getJson('/api/store-locations/' . $store->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'name',
                    'address',
                    'city',
                    'state',
                    'zip_code',
                    'phone',
                    'email',
                    'pickup_available'
                ]
            ]);
    }
    
    /** @test */
    public function admin_can_create_store_location()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $storeData = [
            'name' => 'New Store',
            'address' => '123 New St',
            'city' => 'New City',
            'state' => 'NC',
            'zip_code' => '12345',
            'phone' => '555-5678',
            'email' => 'new@store.com',
            'description' => 'A brand new store',
            'opening_hours' => [
                'Monday' => '9:00 AM - 8:00 PM'
            ],
            'pickup_instructions' => 'Come to the back door',
            'pickup_available' => true
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/store-locations', $storeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ]);
        
        $this->assertDatabaseHas('store_locations', [
            'name' => 'New Store',
            'city' => 'New City'
        ]);
    }
    
    /** @test */
    public function admin_can_update_store_location()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $store = StoreLocation::create([
            'name' => 'Old Store',
            'address' => '123 Old St',
            'city' => 'Old City',
            'state' => 'OC',
            'zip_code' => '54321',
            'pickup_available' => true
        ]);
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $updateData = [
            'name' => 'Updated Store',
            'city' => 'Updated City'
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/admin/store-locations/' . $store->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ]);
        
        $this->assertDatabaseHas('store_locations', [
            'id' => $store->id,
            'name' => 'Updated Store',
            'city' => 'Updated City'
        ]);
    }
    
    /** @test */
    public function admin_can_delete_store_location()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $store = StoreLocation::create([
            'name' => 'Delete Me Store',
            'address' => '123 Delete St',
            'city' => 'Delete City',
            'state' => 'DC',
            'zip_code' => '12345',
            'pickup_available' => true
        ]);
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/admin/store-locations/' . $store->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message'
            ]);
        
        $this->assertDatabaseMissing('store_locations', [
            'id' => $store->id,
        ]);
    }
    
    /** @test */
    public function admin_can_toggle_pickup_availability()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $store = StoreLocation::create([
            'name' => 'Pickup Store',
            'address' => '123 Pickup St',
            'city' => 'Pickup City',
            'state' => 'PC',
            'zip_code' => '12345',
            'pickup_available' => true
        ]);
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('/api/admin/store-locations/' . $store->id . '/toggle-pickup');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'pickup_available'
                ]
            ]);
        
        $this->assertFalse($response->json('data.pickup_available'));
        
        // Toggle back
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('/api/admin/store-locations/' . $store->id . '/toggle-pickup');
        
        $this->assertTrue($response->json('data.pickup_available'));
    }
    
    /** @test */
    public function customer_cannot_manage_store_locations()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        $token = $customer->createToken('auth_token')->plainTextToken;
        
        $storeData = [
            'name' => 'New Store',
            'address' => '123 New St',
            'city' => 'New City',
            'state' => 'NC',
            'zip_code' => '12345',
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/store-locations', $storeData);

        $response->assertStatus(403);
    }
}
