<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);
        
        // Create permissions
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);
        
        // Assign permissions to admin role
        $adminRole->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'delete users'
        ]);
    }
    
    /** @test */
    public function admin_can_view_all_users()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        User::factory()->count(5)->create()->each(function ($user) {
            $user->assignRole('customer');
        });
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data'
            ]);
        
        $responseData = $response->json();
        $this->assertIsArray($responseData['data']);
        
        // Check that admin can see at least the users we created
        $this->assertGreaterThanOrEqual(6, count($responseData['data']));
    }
    
    /** @test */
    public function admin_can_create_new_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer'
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles'
                ]
            ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
        
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
    }
    
    /** @test */
    public function admin_can_update_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'oldemail@example.com'
        ]);
        $user->assignRole('customer');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'newemail@example.com',
            'role' => 'customer'
        ];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/admin/users/' . $user->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data'
            ]);
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'newemail@example.com'
        ]);
    }
    
    /** @test */
    public function admin_can_delete_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        $user->assignRole('customer');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/admin/users/' . $user->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message'
            ]);
        
        // Check that user is soft deleted (has a deleted_at value)
        $this->assertSoftDeleted('users', [
            'id' => $user->id
        ]);
    }
    
    /** @test */
    public function customer_cannot_access_user_management()
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        
        $token = $customer->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/users');

        $response->assertStatus(403);
    }
    
    /** @test */
    public function admin_can_get_available_roles()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name'
                    ]
                ]
            ]);
        
        $this->assertCount(2, $response->json('data')); // admin and customer roles
    }
}
