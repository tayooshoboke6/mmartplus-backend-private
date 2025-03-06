<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeUserAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:make-admin {email : The email of the user to make admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a user an admin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        // Find the user
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }
        
        // Check if admin role exists, create if not
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
            $this->info('Admin role created.');
        }
        
        // Assign admin role
        $user->assignRole('admin');
        
        $this->info("User {$user->name} ({$email}) is now an admin.");
        
        return 0;
    }
}
