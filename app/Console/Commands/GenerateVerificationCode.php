<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SMS\VerificationService;
use Illuminate\Console\Command;

class GenerateVerificationCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'verification:generate {email : User email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a verification code for a user';

    /**
     * Execute the console command.
     *
     * @param VerificationService $verificationService
     * @return int
     */
    public function handle(VerificationService $verificationService)
    {
        // Find user
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email $email not found");
            return 1;
        }
        
        if (!$user->phone_number) {
            $this->error("User doesn't have a phone number");
            return 1;
        }
        
        if ($user->phone_verified) {
            $this->warn("User's phone is already verified");
            if (!$this->confirm('Do you want to generate a new verification code anyway?')) {
                return 0;
            }
        }
        
        // Generate verification code
        $code = $verificationService->generateCode($user);
        
        $this->info("Verification code generated successfully");
        $this->line("Phone: {$user->phone_number}");
        $this->line("Code: {$code->code}");
        $this->line("Expires: {$code->expires_at}");
        
        return 0;
    }
}
