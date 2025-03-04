<?php

namespace Database\Seeders;

use App\Models\StoreLocation;
use Illuminate\Database\Seeder;

class StoreLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        StoreLocation::create([
            'name' => 'M-Mart Main Store',
            'address' => '123 Main Street',
            'city' => 'San Francisco',
            'state' => 'CA',
            'zip_code' => '94105',
            'phone' => '(415) 555-1234',
            'email' => 'mainstore@mmart.com',
            'description' => 'Our flagship store in downtown San Francisco.',
            'opening_hours' => [
                'Monday' => '9:00 AM - 9:00 PM',
                'Tuesday' => '9:00 AM - 9:00 PM',
                'Wednesday' => '9:00 AM - 9:00 PM',
                'Thursday' => '9:00 AM - 9:00 PM',
                'Friday' => '9:00 AM - 10:00 PM',
                'Saturday' => '10:00 AM - 10:00 PM',
                'Sunday' => '10:00 AM - 8:00 PM',
            ],
            'pickup_instructions' => 'Please bring your order confirmation and ID to the pickup counter near the entrance.',
            'pickup_available' => true,
            'latitude' => 37.7749,
            'longitude' => -122.4194,
        ]);

        StoreLocation::create([
            'name' => 'M-Mart Oakland',
            'address' => '456 Broadway',
            'city' => 'Oakland',
            'state' => 'CA',
            'zip_code' => '94607',
            'phone' => '(510) 555-9876',
            'email' => 'oakland@mmart.com',
            'description' => 'Our Oakland location with ample parking space.',
            'opening_hours' => [
                'Monday' => '10:00 AM - 8:00 PM',
                'Tuesday' => '10:00 AM - 8:00 PM',
                'Wednesday' => '10:00 AM - 8:00 PM',
                'Thursday' => '10:00 AM - 8:00 PM',
                'Friday' => '10:00 AM - 9:00 PM',
                'Saturday' => '10:00 AM - 9:00 PM',
                'Sunday' => '11:00 AM - 7:00 PM',
            ],
            'pickup_instructions' => 'Pickup counter is located at the back of the store near the electronics department.',
            'pickup_available' => true,
            'latitude' => 37.8044,
            'longitude' => -122.2712,
        ]);

        StoreLocation::create([
            'name' => 'M-Mart San Jose',
            'address' => '789 Technology Drive',
            'city' => 'San Jose',
            'state' => 'CA',
            'zip_code' => '95110',
            'phone' => '(408) 555-5678',
            'email' => 'sanjose@mmart.com',
            'description' => 'Our newest location in the heart of Silicon Valley.',
            'opening_hours' => [
                'Monday' => '9:00 AM - 9:00 PM',
                'Tuesday' => '9:00 AM - 9:00 PM',
                'Wednesday' => '9:00 AM - 9:00 PM',
                'Thursday' => '9:00 AM - 9:00 PM',
                'Friday' => '9:00 AM - 10:00 PM',
                'Saturday' => '9:00 AM - 10:00 PM',
                'Sunday' => '10:00 AM - 8:00 PM',
            ],
            'pickup_instructions' => 'Please use the dedicated pickup entrance on the east side of the building.',
            'pickup_available' => true,
            'latitude' => 37.3382,
            'longitude' => -121.8863,
        ]);
    }
}
