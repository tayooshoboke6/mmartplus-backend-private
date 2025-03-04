<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VerificationCode>
 */
class VerificationCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'phone_number' => fake()->phoneNumber(),
            'code' => (string) random_int(100000, 999999),
            'expires_at' => Carbon::now()->addMinutes(15),
            'is_used' => false,
        ];
    }
    
    /**
     * Indicate that the verification code is expired.
     *
     * @return static
     */
    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => Carbon::now()->subMinutes(5),
            ];
        });
    }
    
    /**
     * Indicate that the verification code is used.
     *
     * @return static
     */
    public function used()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_used' => true,
            ];
        });
    }
}
