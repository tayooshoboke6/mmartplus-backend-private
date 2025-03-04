<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VerificationCode;
use App\Services\SMS\VerificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class PhoneVerificationTest extends TestCase
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
    public function user_can_request_verification_code()
    {
        // Create user with unverified phone
        $user = User::factory()->create([
            'phone_number' => '1234567890',
            'phone_verified' => false,
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/phone/verify/send');
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Verification code sent successfully. Please check your phone.'
            ]);
            
        // Check that a verification code was created
        $this->assertDatabaseHas('verification_codes', [
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'is_used' => false,
        ]);
    }
    
    /** @test */
    public function user_without_phone_number_cannot_request_verification()
    {
        // Create user without phone number
        $user = User::factory()->create([
            'phone_number' => null,
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/phone/verify/send');
        
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'No phone number provided for this user.'
            ]);
    }
    
    /** @test */
    public function user_with_verified_phone_cannot_request_verification()
    {
        // Create user with verified phone
        $user = User::factory()->create([
            'phone_number' => '1234567890',
            'phone_verified' => true,
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/phone/verify/send');
        
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Phone number is already verified.'
            ]);
    }
    
    /** @test */
    public function user_can_verify_phone_with_valid_code()
    {
        // Create user with unverified phone
        $user = User::factory()->create([
            'phone_number' => '1234567890',
            'phone_verified' => false,
        ]);
        
        // Create verification code
        $verificationCode = VerificationCode::factory()->create([
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'code' => '123456',
            'expires_at' => Carbon::now()->addMinutes(15),
            'is_used' => false,
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/phone/verify', [
            'code' => '123456'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Phone number verified successfully.'
            ]);
            
        // Check user has verified phone
        $this->assertTrue($user->fresh()->phone_verified);
        
        // Check verification code is marked as used
        $this->assertTrue($verificationCode->fresh()->is_used);
    }
    
    /** @test */
    public function user_cannot_verify_with_invalid_code()
    {
        // Create user with unverified phone
        $user = User::factory()->create([
            'phone_number' => '1234567890',
            'phone_verified' => false,
        ]);
        
        // Create verification code
        $verificationCode = VerificationCode::factory()->create([
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'code' => '123456',
            'expires_at' => Carbon::now()->addMinutes(15),
            'is_used' => false,
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/phone/verify', [
            'code' => '654321' // Wrong code
        ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.'
            ]);
            
        // Check user still has unverified phone
        $this->assertFalse($user->fresh()->phone_verified);
        
        // Check verification code is still not used
        $this->assertFalse($verificationCode->fresh()->is_used);
    }
    
    /** @test */
    public function user_cannot_verify_with_expired_code()
    {
        // Create user with unverified phone
        $user = User::factory()->create([
            'phone_number' => '1234567890',
            'phone_verified' => false,
        ]);
        
        // Create verification code that's expired
        $verificationCode = VerificationCode::factory()->expired()->create([
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'code' => '123456',
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/phone/verify', [
            'code' => '123456'
        ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.'
            ]);
            
        // Check user still has unverified phone
        $this->assertFalse($user->fresh()->phone_verified);
    }
    
    /** @test */
    public function user_cannot_verify_with_used_code()
    {
        // Create user with unverified phone
        $user = User::factory()->create([
            'phone_number' => '1234567890',
            'phone_verified' => false,
        ]);
        
        // Create verification code that's already used
        $verificationCode = VerificationCode::factory()->used()->create([
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'code' => '123456',
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);
        
        Sanctum::actingAs($user);
        
        $response = $this->postJson('/api/phone/verify', [
            'code' => '123456'
        ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.'
            ]);
            
        // Check user still has unverified phone
        $this->assertFalse($user->fresh()->phone_verified);
    }
}
