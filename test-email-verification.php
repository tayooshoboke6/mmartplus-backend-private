<?php

require 'vendor/autoload.php';

// Load the environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Create a new User for testing
function createTestUser() {
    $user = new \App\Models\User();
    $user->name = 'Test User ' . time();
    $user->email = 'test' . time() . '@example.com';
    $user->password = \Illuminate\Support\Facades\Hash::make('Password123');
    $user->save();
    echo "Created user: {$user->name} ({$user->email})\n";
    return $user;
}

// Send verification email to the user
function sendVerificationEmail($user) {
    $verificationService = new \App\Services\Email\EmailVerificationService(
        app(\App\Services\Email\EmailServiceInterface::class)
    );
    
    $code = $verificationService->sendVerificationEmail($user);
    echo "Sent verification email to {$user->email} with code: {$code->code}\n";
    return $code;
}

// Verify the email with the code
function verifyEmail($user, $code) {
    $verificationService = new \App\Services\Email\EmailVerificationService(
        app(\App\Services\Email\EmailServiceInterface::class)
    );
    
    $verified = $verificationService->verifyEmail($user, $code);
    if ($verified) {
        echo "Email verification successful!\n";
    } else {
        echo "Email verification failed!\n";
    }
    return $verified;
}

// Test the email verification flow
echo "Starting email verification test...\n";
echo "Creating test user...\n";
$user = createTestUser();

echo "\nSending verification email...\n";
$verificationCode = sendVerificationEmail($user);

echo "\nVerifying email...\n";
$verified = verifyEmail($user, $verificationCode->code);

echo "\nTest completed!\n";
if ($verified) {
    echo "✅ Email verification flow works correctly!\n";
} else {
    echo "❌ Email verification flow failed!\n";
}

// Clean up (optional)
// $user->delete();
// echo "Test user deleted.\n";
