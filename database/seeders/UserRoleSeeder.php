<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $customerRole = Role::firstOrCreate(['name' => 'customer']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);

        // Create permissions
        $permissions = [
            // Product permissions
            'view products', 'create products', 'edit products', 'delete products',
            // Category permissions
            'view categories', 'create categories', 'edit categories', 'delete categories',
            // Order permissions
            'view any orders', 'view own orders', 'create orders', 'edit orders', 'update order status',
            // User permissions
            'view users', 'create users', 'edit users', 'delete users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole->givePermissionTo([
            'view products', 'create products', 'edit products', 'delete products',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view any orders', 'edit orders', 'update order status',
            'view users', 'create users', 'edit users',
        ]);

        $customerRole->givePermissionTo([
            'view products',
            'view own orders', 'create orders',
        ]);

        $superAdminRole->givePermissionTo(Permission::all());

        // Create admin user
        $admin = User::where('email', 'admin@mmartplus.com')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'M-Mart Admin',
                'email' => 'admin@mmartplus.com',
                'password' => Hash::make('Admin@123'),
                'email_verified_at' => now(),
            ]);
        }
        
        // Assign admin role
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create customer user
        $customer = User::where('email', 'customer@mmartplus.com')->first();
        if (!$customer) {
            $customer = User::create([
                'name' => 'Test Customer',
                'email' => 'customer@mmartplus.com',
                'password' => Hash::make('Customer@123'),
                'email_verified_at' => now(),
            ]);
        }
        
        // Assign customer role
        if (!$customer->hasRole('customer')) {
            $customer->assignRole('customer');
        }

        $this->command->info('Users and roles created successfully!');
    }
}
