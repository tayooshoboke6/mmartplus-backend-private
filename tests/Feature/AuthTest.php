<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Make sure roles exist
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }
    
    /** @test */
    public function user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone_number' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'phone_number',
                        'roles'
                    ],
                    'token'
                ]
            ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'phone_number' => '+1234567890',
        ]);
        
        // Check if user has customer role
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
    }
    
    /** @test */
    public function user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('customer');
        
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles'
                    ],
                    'token'
                ]
            ]);
    }
    
    /** @test */
    public function user_cannot_login_with_incorrect_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422);
    }
    
    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('customer');
        
        $token = $user->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'User logged out successfully'
            ]);
        
        $this->assertCount(0, $user->tokens);
    }
    
    /** @test */
    public function authenticated_user_can_get_their_info()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user->assignRole('customer');
        
        $token = $user->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles'
                    ],
                    'is_admin'
                ]
            ]);
            
        $this->assertFalse($response->json('data.is_admin'));
    }
    
    /** @test */
    public function admin_user_has_admin_flag_in_response()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $admin->assignRole('admin');
        
        $token = $admin->createToken('auth_token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.is_admin'));
    }
    
    /** @test */
    public function user_cannot_register_with_duplicate_phone_number()
    {
        // Create a user with a phone number
        User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'phone_number' => '+9876543210',
            'password' => bcrypt('password'),
        ]);

        // Try to register with the same phone number
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'phone_number' => '+9876543210', // same phone number
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone_number']);
    }
}
