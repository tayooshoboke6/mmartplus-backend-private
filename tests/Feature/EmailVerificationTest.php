<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the non-authenticated send verification code endpoint.
     *
     * @return void
     */
    public function test_send_non_auth_verification_code()
    {
        // Create a test user
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        // Send verification code
        $response = $this->postJson('/api/email/non-auth/send', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Verification code sent successfully. Please check your email.'
            ]);

        // Assert that a verification code was created
        $this->assertDatabaseHas('verification_codes', [
            'user_id' => $user->id,
            'phone_number' => null,
            'is_used' => false,
        ]);
    }

    /**
     * Test the non-authenticated verify email endpoint.
     *
     * @return void
     */
    public function test_verify_non_auth_email()
    {
        // Create a test user
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        // Create a verification code
        $code = '123456';
        $verificationCode = VerificationCode::create([
            'user_id' => $user->id,
            'phone_number' => null,
            'code' => $code,
            'expires_at' => now()->addMinutes(30),
            'is_used' => false,
        ]);

        // Verify the email
        $response = $this->postJson('/api/email/non-auth/verify', [
            'email' => $user->email,
            'code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email verified successfully.'
            ]);

        // Assert that the user's email is now verified
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Assert that the verification code is now used
        $this->assertTrue($verificationCode->fresh()->is_used);
    }

    /**
     * Test that an error is returned when trying to verify with an invalid code.
     *
     * @return void
     */
    public function test_verify_with_invalid_code()
    {
        // Create a test user
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        // Create a verification code
        $validCode = '123456';
        VerificationCode::create([
            'user_id' => $user->id,
            'phone_number' => null,
            'code' => $validCode,
            'expires_at' => now()->addMinutes(30),
            'is_used' => false,
        ]);

        // Try to verify with an invalid code
        $invalidCode = '654321';
        $response = $this->postJson('/api/email/non-auth/verify', [
            'email' => $user->email,
            'code' => $invalidCode,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.'
            ]);

        // Assert that the user's email is still not verified
        $this->assertNull($user->fresh()->email_verified_at);
    }

    /**
     * Test the complete email verification flow.
     *
     * @return void
     */
    public function test_complete_verification_flow()
    {
        // Create a test user
        $user = User::factory()->create([
            'email_verified_at' => null
        ]);

        // Send verification code
        $sendResponse = $this->postJson('/api/email/non-auth/send', [
            'email' => $user->email,
        ]);

        $sendResponse->assertStatus(200);

        // Get the verification code from the database
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('phone_number', null)
            ->where('is_used', false)
            ->first();

        $this->assertNotNull($verificationCode);

        // Verify the email
        $verifyResponse = $this->postJson('/api/email/non-auth/verify', [
            'email' => $user->email,
            'code' => $verificationCode->code,
        ]);

        $verifyResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email verified successfully.'
            ]);

        // Assert that the user's email is now verified
        $this->assertNotNull($user->fresh()->email_verified_at);

        // Try to log in
        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password', // Default password from factory
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user',
            ]);
    }
}
