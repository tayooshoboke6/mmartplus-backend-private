<?php
// This script tests the email verification flow and shows the OTP code

// Load Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

echo "Testing Email Verification OTP Code\n";
echo "--------------------------------\n\n";

// Create a test user if needed
$email = 'test-' . time() . '@example.com';
$user = new User();
$user->name = 'Test User';
$user->email = $email;
$user->password = Hash::make('password123');
$user->phone_number = '+1234567890'; // Add a phone number 
$user->save();

echo "Created test user: $email\n\n";

// Generate a verification code manually
$code = (string) random_int(100000, 999999);
echo "Generated verification code: $code\n";

// Create the verification code record
$verificationCode = new VerificationCode();
$verificationCode->user_id = $user->id;
$verificationCode->phone_number = '';  // Use empty string instead of null
$verificationCode->code = $code;
$verificationCode->expires_at = Carbon::now()->addMinutes(30);
$verificationCode->is_used = false;
$verificationCode->save();

echo "Saved verification code to database\n\n";

// Optional: Mark the email as verified immediately to allow login
echo "Do you want to mark the email as verified immediately? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if ($line === 'y') {
    $user->email_verified_at = Carbon::now();
    $user->save();
    echo "Email marked as verified. You can now login with:\n";
    echo "Email: $email\n";
    echo "Password: password123\n\n";
} else {
    echo "Email not marked as verified. You will need to verify using the code.\n\n";
}

// Log as if the DummyEmailService was used
Log::info("DUMMY EMAIL SERVICE - Would have sent an email to {$email}");
Log::info("Subject: Verify Your Email Address - M-Mart+");
Log::info("Verification Code: {$code}");

echo "\n";
echo "Verification Code: " . $code . "\n";
echo "\n";
echo "You can now use this code to verify the email in the frontend\n";
echo "or use it with the API: POST /api/email/non-auth/verify\n";
echo "with payload: { \"email\": \"$email\", \"code\": \"" . $code . "\" }\n";

// For convenience, create another test with fixed values
echo "\n";
echo "Would you like to create a test user with fixed credentials? (y/n): ";
$line = trim(fgets($handle));
if ($line === 'y') {
    $fixedEmail = 'test@example.com';
    
    // Check if the user already exists
    $existingUser = User::where('email', $fixedEmail)->first();
    if ($existingUser) {
        echo "Fixed test user already exists.\n";
        $existingUser->email_verified_at = Carbon::now(); // Ensure it's verified
        $existingUser->password = Hash::make('password123'); // Reset password
        $existingUser->save();
    } else {
        // Create a new user with fixed email
        $fixedUser = new User();
        $fixedUser->name = 'Fixed Test User';
        $fixedUser->email = $fixedEmail;
        $fixedUser->password = Hash::make('password123');
        $fixedUser->phone_number = '+1987654321';
        $fixedUser->email_verified_at = Carbon::now(); // Mark as verified
        $fixedUser->save();
    }
    
    echo "Fixed test user created/updated:\n";
    echo "Email: $fixedEmail\n";
    echo "Password: password123\n";
}

fclose($handle);
