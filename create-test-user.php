<?php
// This script creates a verified test user for login testing

// Load Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "Creating Test User\n";
echo "----------------\n\n";

// Fixed credentials for easier testing
$email = 'test123@example.com';
$password = 'password123';
$phoneNumber = '+1' . rand(1000000000, 9999999999); // Generate a random phone number

// Check if user already exists
$user = User::where('email', $email)->first();

if ($user) {
    echo "User already exists. Updating...\n";
    $user->password = Hash::make($password);
    $user->email_verified_at = Carbon::now();
    $user->save();
} else {
    echo "Creating new user...\n";
    $user = new User();
    $user->name = 'Test User';
    $user->email = $email;
    $user->password = Hash::make($password);
    $user->phone_number = $phoneNumber;
    $user->email_verified_at = Carbon::now(); // Mark as verified immediately
    $user->save();
}

echo "\nTest user created successfully!\n";
echo "------------------------------\n";
echo "Email: $email\n";
echo "Password: $password\n";
echo "Phone: " . $user->phone_number . "\n";
echo "Verified: Yes\n\n";

// Print API login details
echo "For API debugging:\n";
echo "POST to: /api/login\n";
echo "With payload: \n";
echo json_encode(['email' => $email, 'password' => $password], JSON_PRETTY_PRINT) . "\n\n";

// List all users for debugging
echo "All users in database:\n";
$users = User::all(['id', 'name', 'email', 'email_verified_at']);
foreach ($users as $u) {
    echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, Verified: " . 
         ($u->email_verified_at ? 'Yes' : 'No') . "\n";
}

// Output API route list
echo "\n\nAPI Routes for reference:\n";
echo "------------------------\n";
\Illuminate\Support\Facades\Artisan::call('route:list', ['--path' => 'api']);
echo \Illuminate\Support\Facades\Artisan::output();
