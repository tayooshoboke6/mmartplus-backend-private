<?php

namespace App\Providers;

use App\Services\SMS\DummySMSService;
use App\Services\SMS\LocalSMSService;
use App\Services\SMS\SMSServiceInterface;
use App\Services\SMS\VerificationService;
use Illuminate\Support\ServiceProvider;

class SMSServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Bind the SMS service implementation based on configuration
        $this->app->singleton(SMSServiceInterface::class, function ($app) {
            $provider = config('services.sms.provider', 'dummy');
            
            return match($provider) {
                'local' => new LocalSMSService(),
                default => new DummySMSService(),
            };
        });
        
        // Bind the verification service
        $this->app->singleton(VerificationService::class, function ($app) {
            return new VerificationService($app->make(SMSServiceInterface::class));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
