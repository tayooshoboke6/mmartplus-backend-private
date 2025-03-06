<?php

namespace Tests\Feature;

use Mockery;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'customer']);
    }

    /** @test */
    public function it_can_redirect_to_google_provider()
    {
        $response = $this->getJson('/api/auth/google/redirect');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'redirect_url'
            ]);
    }
    
    /** @test */
    public function it_rejects_invalid_provider()
    {
        $response = $this->getJson('/api/auth/invalid-provider/redirect');
        
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid provider'
            ]);
    }
    
    /** @test */
    public function it_can_handle_google_callback_for_new_user()
    {
        // Mock Socialite
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')
            ->andReturn('123456789')
            ->shouldReceive('getName')
            ->andReturn('Test User')
            ->shouldReceive('getEmail')
            ->andReturn('test@example.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://example.com/avatar.jpg');
            
        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::mock()
                ->shouldReceive('stateless')
                ->andReturn(Mockery::self())
                ->shouldReceive('user')
                ->andReturn($abstractUser)
                ->getMock()
            );
            
        $response = $this->getJson('/api/auth/google/callback?code=test-code');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user',
                    'token'
                ]
            ]);
            
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'social_id' => '123456789',
            'social_type' => 'google'
        ]);
        
        // Verify user has customer role
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
    }
    
    /** @test */
    public function it_can_handle_google_callback_for_existing_user()
    {
        // Create existing user
        $user = User::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('customer');
        
        // Mock Socialite
        $abstractUser = Mockery::mock(SocialiteUser::class);
        $abstractUser->shouldReceive('getId')
            ->andReturn('987654321')
            ->shouldReceive('getName')
            ->andReturn('Existing User')
            ->shouldReceive('getEmail')
            ->andReturn('existing@example.com')
            ->shouldReceive('getAvatar')
            ->andReturn('https://example.com/avatar.jpg');
            
        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::mock()
                ->shouldReceive('stateless')
                ->andReturn(Mockery::self())
                ->shouldReceive('user')
                ->andReturn($abstractUser)
                ->getMock()
            );
            
        $response = $this->getJson('/api/auth/google/callback?code=test-code');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user',
                    'token'
                ]
            ]);
            
        $this->assertDatabaseHas('users', [
            'email' => 'existing@example.com',
            'social_id' => '987654321',
            'social_type' => 'google'
        ]);
    }
}
