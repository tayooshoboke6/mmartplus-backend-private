<?php

namespace Database\Factories;

use App\Models\StoreLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoreLocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StoreLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->company() . ' Store',
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'zip_code' => $this->faker->postcode(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'description' => $this->faker->paragraph(),
            'opening_hours' => json_encode([
                'monday' => ['09:00 - 18:00'],
                'tuesday' => ['09:00 - 18:00'],
                'wednesday' => ['09:00 - 18:00'],
                'thursday' => ['09:00 - 18:00'],
                'friday' => ['09:00 - 18:00'],
                'saturday' => ['10:00 - 16:00'],
                'sunday' => ['closed'],
            ]),
            'pickup_instructions' => $this->faker->paragraph(),
            'pickup_available' => $this->faker->boolean(80),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
