<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\Email\EmailVerificationService;
use App\Services\Email\EmailServiceInterface;
use Illuminate\Support\Facades\Hash;

class TestEmailVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-verification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the email verification flow';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(EmailServiceInterface $emailService)
    {
        $this->info('Starting email verification test...');
        
        // Create email verification service
        $verificationService = new EmailVerificationService($emailService);
        
        // Create a test user
        $this->info('Creating test user...');
        $user = new User();
        $user->name = 'Test User ' . time();
        $user->email = 'test' . time() . '@example.com';
        $user->password = Hash::make('Password123');
        $user->save();
        
        $this->info("Created user: {$user->name} ({$user->email})");
        
        // Send verification email
        $this->info("\nSending verification email...");
        $verificationCode = $verificationService->sendVerificationEmail($user);
        $this->info("Sent verification email to {$user->email} with code: {$verificationCode->code}");
        
        // Verify the email
        $this->info("\nVerifying email...");
        $verified = $verificationService->verifyEmail($user, $verificationCode->code);
        
        if ($verified) {
            $this->info("Email verification successful!");
        } else {
            $this->error("Email verification failed!");
        }
        
        // Refresh user from database to confirm email_verified_at is set
        $user->refresh();
        $this->info("User email_verified_at: " . ($user->email_verified_at ?? 'NULL'));
        
        // Check non-authenticated endpoints
        $this->info("\nTesting non-authenticated endpoints...");
        
        // Create a new test user for non-auth testing
        $nonAuthUser = new User();
        $nonAuthUser->name = 'Non-Auth Test User ' . time();
        $nonAuthUser->email = 'non-auth-test' . time() . '@example.com';
        $nonAuthUser->password = Hash::make('Password123');
        $nonAuthUser->save();
        
        $this->info("Created non-auth test user: {$nonAuthUser->name} ({$nonAuthUser->email})");
        
        // Test the entire flow as if it were through the API
        $this->info("Testing email verification controller (non-auth)...");
        
        $request = new \Illuminate\Http\Request();
        $request->merge(['email' => $nonAuthUser->email]);
        
        $controller = new \App\Http\Controllers\EmailVerificationController($verificationService);
        $response = $controller->sendNonAuth($request);
        
        $this->info("Send non-auth response: " . $response->getContent());
        
        // Get the verification code from the database
        $verificationCode = \App\Models\VerificationCode::where('user_id', $nonAuthUser->id)
            ->where('phone_number', null)
            ->where('is_used', false)
            ->first();
            
        if ($verificationCode) {
            $this->info("Verification code: {$verificationCode->code}");
            
            // Verify the code
            $request = new \Illuminate\Http\Request();
            $request->merge([
                'email' => $nonAuthUser->email,
                'code' => $verificationCode->code
            ]);
            
            $response = $controller->verifyNonAuth($request);
            $this->info("Verify non-auth response: " . $response->getContent());
            
            // Refresh user to see if email is verified
            $nonAuthUser->refresh();
            $this->info("Non-auth user email_verified_at: " . ($nonAuthUser->email_verified_at ?? 'NULL'));
        } else {
            $this->error("No verification code found for non-auth user");
        }
        
        $this->info("\nTest completed!");
        
        // Cleanup
        $this->info("Cleaning up test users...");
        $user->delete();
        $nonAuthUser->delete();
        $this->info("Test users deleted.");
        
        return 0;
    }
}
