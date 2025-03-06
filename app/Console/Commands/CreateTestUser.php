<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-test {--email=test@example.com : Email for the test user} {--password=password : Password for the test user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for development';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->option('email');
        $password = $this->option('password');
        
        // Check if user already exists
        $user = User::where('email', $email)->first();
        
        if ($user) {
            $this->info("User with email {$email} already exists.");
            return 0;
        }
        
        // Create new user
        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
        
        $this->info("Test user created with email: {$email} and password: {$password}");
        
        return 0;
    }
}
