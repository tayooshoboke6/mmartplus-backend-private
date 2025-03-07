<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
        $this->call(TestUsersSeeder::class);
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            StoreLocationSeeder::class,
            ProductCategorySeeder::class, // Our new category seeder
            ProductSeeder::class, // Updated with Nigerian products
            UserRoleSeeder::class, // Our new user and role seeder
            OrderSeeder::class,
        ]);
        
        // Admin and customer users are now created in UserRoleSeeder
    }
}
