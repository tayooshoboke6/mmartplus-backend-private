<?php

namespace App\Providers;

use App\Services\Email\BrevoEmailService;
use App\Services\Email\DummyEmailService;
use App\Services\Email\EmailServiceInterface;
use App\Services\Email\EmailVerificationService;
use App\Services\Email\NotificationService;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Bind the Email service implementation based on configuration
        $this->app->singleton(EmailServiceInterface::class, function ($app) {
            $provider = config('services.email.provider', 'dummy');
            
            return match($provider) {
                'brevo' => new BrevoEmailService(),
                default => new DummyEmailService(),
            };
        });
        
        // Bind the email verification service
        $this->app->singleton(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService($app->make(EmailServiceInterface::class));
        });
        
        // Bind the notification service
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService($app->make(EmailServiceInterface::class));
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
