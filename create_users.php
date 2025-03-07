<?php
// Simple script to create test users for M-Mart+
// This script should be run from the Laravel project root directory

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

echo "Creating test users for M-Mart+...\n\n";

// Check if admin role exists
$adminRole = Role::where('name', 'admin')->first();
if (!$adminRole) {
    echo "Error: 'admin' role not found in the database. Make sure roles are seeded.\n";
    exit(1);
}

// Check if customer role exists
$customerRole = Role::where('name', 'customer')->first();
if (!$customerRole) {
    echo "Error: 'customer' role not found in the database. Make sure roles are seeded.\n";
    exit(1);
}

// Create admin user if it doesn't exist
$adminUser = User::where('email', 'admin@mmartplus.com')->first();
if (!$adminUser) {
    $adminUser = new User();
    $adminUser->name = 'Admin User';
    $adminUser->email = 'admin@mmartplus.com';
    $adminUser->phone_number = '1234567890';
    $adminUser->password = Hash::make('adminpassword');
    $adminUser->email_verified_at = now();
    $adminUser->save();
    
    // Assign admin role
    $adminUser->assignRole('admin');
    echo "Admin user created successfully.\n";
} else {
    echo "Admin user already exists, updating role...\n";
    $adminUser->syncRoles(['admin']);
}

// Create customer user if it doesn't exist
$customerUser = User::where('email', 'customer@mmartplus.com')->first();
if (!$customerUser) {
    $customerUser = new User();
    $customerUser->name = 'Customer User';
    $customerUser->email = 'customer@mmartplus.com';
    $customerUser->phone_number = '0987654321';
    $customerUser->password = Hash::make('customerpassword');
    $customerUser->email_verified_at = now();
    $customerUser->save();
    
    // Assign customer role
    $customerUser->assignRole('customer');
    echo "Customer user created successfully.\n";
} else {
    echo "Customer user already exists, updating role...\n";
    $customerUser->syncRoles(['customer']);
}

echo "\nTest users created/updated successfully!\n";
echo "You can now log in with:\n";
echo "Admin: admin@mmartplus.com / adminpassword\n";
echo "Customer: customer@mmartplus.com / customerpassword\n";
echo "\nPlease remember to change these passwords in production!\n";
