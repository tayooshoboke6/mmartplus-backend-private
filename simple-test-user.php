<?php
// This script creates a simple verified test user for login testing
// with minimal dependencies to avoid errors

// Load Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

echo "Creating Simple Test User\n";
echo "------------------------\n\n";

// Fixed credentials for easier testing
$email = 'simple-test@example.com';
$password = 'password123';
$phoneNumber = '+1' . rand(1000000000, 9999999999); // Generate a random phone number

try {
    // Check if user already exists
    $user = User::where('email', $email)->first();

    if ($user) {
        echo "User already exists. Updating...\n";
        $user->password = Hash::make($password);
        $user->email_verified_at = Carbon::now();
        $user->save();
        echo "User updated successfully.\n";
    } else {
        echo "Creating new user...\n";
        $user = new User();
        $user->name = 'Simple Test User';
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->phone_number = $phoneNumber;
        $user->email_verified_at = Carbon::now(); // Mark as verified immediately
        $user->save();
        echo "User created successfully.\n";
    }

    echo "\nTest user ready for login:\n";
    echo "------------------------\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "Phone: " . $user->phone_number . "\n";
    echo "Verified: Yes\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
