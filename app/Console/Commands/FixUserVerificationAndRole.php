<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FixUserVerificationAndRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:fix-verification-and-role {email} {--role=admin} {--verify=true} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix user verification and assign role';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->option('role');
        $verify = $this->option('verify') === 'true';
        $password = $this->option('password');
        
        // Find the user
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            
            if ($this->confirm('Do you want to create this user?')) {
                // Create the user if it doesn't exist
                $password = $password ?: 'password123'; // Default password if not provided
                
                $user = User::create([
                    'name' => 'Test Admin',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => $verify ? now() : null,
                ]);
                
                $this->info("User {$email} created successfully with password: {$password}");
            } else {
                return 1;
            }
        }
        
        // Verify email if requested
        if ($verify && !$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
            $this->info("User {$email} email has been verified.");
        }
        
        // Check if role exists, create if not
        $roleModel = Role::where('name', $role)->first();
        if (!$roleModel) {
            $roleModel = Role::create(['name' => $role, 'guard_name' => 'web']);
            $this->info("Role {$role} created.");
        }
        
        // Assign role if user doesn't have it
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
            $this->info("Role {$role} assigned to user {$email}.");
        } else {
            $this->info("User {$email} already has role {$role}.");
        }
        
        // Update password if provided
        if ($password) {
            $user->password = Hash::make($password);
            $user->save();
            $this->info("Password updated for user {$email}.");
        }
        
        // Load roles to check
        $user->load('roles');
        
        $this->info("User {$email} has the following roles: " . $user->roles->pluck('name')->implode(', '));
        
        return 0;
    }
}
