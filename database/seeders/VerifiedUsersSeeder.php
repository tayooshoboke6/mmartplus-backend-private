<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class VerifiedUsersSeeder extends Seeder
{
    /**
     * Run the database seeds to create verified test users.
     * Creates both admin and customer test accounts with predefined credentials
     * and verified emails.
     *
     * @return void
     */
    public function run()
    {
        // Start a transaction to ensure all operations succeed or fail together
        DB::beginTransaction();

        try {
            // Create admin user
            $admin = User::create([
                'name' => 'Test Admin',
                'email' => 'testadmin@mmartplus.com',
                'phone_number' => '5551234567',
                'password' => Hash::make('test123admin'),
                'email_verified_at' => now(), // Ensure email is verified
            ]);
            
            // Assign admin role
            $admin->assignRole('admin');
            
            $this->command->info('Admin user created with verified email: testadmin@mmartplus.com / test123admin');

            // Create customer user
            $customer = User::create([
                'name' => 'Test Customer',
                'email' => 'testcustomer@mmartplus.com',
                'phone_number' => '5559876543',
                'password' => Hash::make('test123customer'),
                'email_verified_at' => now(), // Ensure email is verified
            ]);
            
            // Assign customer role
            $customer->assignRole('customer');
            
            $this->command->info('Customer user created with verified email: testcustomer@mmartplus.com / test123customer');

            // Commit transaction
            DB::commit();
            $this->command->info('All test users created successfully!');
            
        } catch (\Exception $e) {
            // Rollback transaction if any operation fails
            DB::rollBack();
            $this->command->error('Failed to create test users: ' . $e->getMessage());
        }
    }
}
