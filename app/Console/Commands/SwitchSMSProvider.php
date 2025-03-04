<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SwitchSMSProvider extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:provider {provider : The SMS provider to use (dummy, local)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch the active SMS provider in the environment file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $provider = $this->argument('provider');
        
        // Validate provider
        if (!in_array($provider, ['dummy', 'local'])) {
            $this->error("Invalid provider: $provider. Available providers are: dummy, local");
            return 1;
        }
        
        // Get .env file path
        $envPath = base_path('.env');
        
        // Check if .env file exists
        if (!File::exists($envPath)) {
            $this->error('.env file not found');
            return 1;
        }
        
        // Get current content
        $content = File::get($envPath);
        
        // Replace or add SMS_PROVIDER
        if (preg_match('/^SMS_PROVIDER=.*$/m', $content)) {
            // Replace existing value
            $content = preg_replace('/^SMS_PROVIDER=.*$/m', "SMS_PROVIDER=$provider", $content);
        } else {
            // Add new value
            $content .= "\n\n# SMS Provider\nSMS_PROVIDER=$provider\nSMS_API_KEY=\nSMS_API_SECRET=\nSMS_SENDER_ID=MMART\n";
        }
        
        // Save changes
        File::put($envPath, $content);
        
        $this->info("SMS provider switched to '$provider'");
        
        // Clear config cache to apply changes
        $this->call('config:clear');
        
        return 0;
    }
}
