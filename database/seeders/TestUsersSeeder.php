<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds to create test users.
     * Creates both admin and customer test accounts with predefined credentials.
     *
     * @return void
     */
    public function run()
    {
        // Start a transaction to ensure all operations succeed or fail together
        DB::beginTransaction();

        try {
            // Clear existing test users if they exist (to avoid duplicates)
            $this->clearExistingTestUsers();

            // Create admin user
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@mmartplus.com',
                'phone_number' => '1234567890',
                'password' => Hash::make('adminpassword'),
                'email_verified_at' => now(),
            ]);
            
            // Assign admin role
            $admin->assignRole('admin');
            
            $this->command->info('Admin user created successfully: admin@mmartplus.com / adminpassword');

            // Create customer user
            $customer = User::create([
                'name' => 'Customer User',
                'email' => 'customer@mmartplus.com',
                'phone_number' => '0987654321',
                'password' => Hash::make('customerpassword'),
                'email_verified_at' => now(),
            ]);
            
            // Assign customer role (though this should be automatic in your implementation)
            $customer->assignRole('customer');
            
            $this->command->info('Customer user created successfully: customer@mmartplus.com / customerpassword');

            // Commit transaction
            DB::commit();
            $this->command->info('Test users created successfully!');
        } catch (\Exception $e) {
            // Roll back transaction if something fails
            DB::rollBack();
            $this->command->error('Failed to create test users: ' . $e->getMessage());
        }
    }

    /**
     * Clear existing test users to avoid duplicate records
     */
    private function clearExistingTestUsers()
    {
        $testEmails = ['admin@mmartplus.com', 'customer@mmartplus.com'];
        
        foreach ($testEmails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                // Remove role assignments first to avoid foreign key constraints
                $user->syncRoles([]);
                $user->delete();
                $this->command->info("Removed existing user: {$email}");
            }
        }
    }
}
