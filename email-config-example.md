# Email Configuration for M-Mart+

This document outlines the required environment variables for configuring the email service in M-Mart+.

## Basic Configuration

Add these variables to your `.env` file:

```
# Email Service Provider Configuration
EMAIL_PROVIDER=brevo    # Options: 'brevo' for production, 'dummy' for development

# Brevo (formerly Sendinblue) API Configuration
BREVO_API_KEY=your_brevo_api_key_here
BREVO_FROM_EMAIL=noreply@mmartplus.com
BREVO_FROM_NAME="M-Mart+ Team"

# Email Settings
EMAIL_VERIFICATION_EXPIRY=30    # Verification code expiry time in minutes
```

## Testing the Email Service

To test if your email configuration is working correctly:

1. Use the `php artisan tinker` command
2. Run the following code to send a test email:

```php
$emailService = app()->make(\App\Services\Email\EmailServiceInterface::class);
$emailService->send('your-test-email@example.com', 'Test Email', '<p>This is a test email from M-Mart+</p>', 'M-Mart+ Team');
```

## Switching to Dummy Email Service

For development and testing without sending real emails, set `EMAIL_PROVIDER=dummy` in your `.env` file. This will use the DummyEmailService which logs emails instead of sending them.

## Creating an Email Service Provider

To create a new email service provider:

1. Create a new class that implements the `EmailServiceInterface`
2. Register it in the `AppServiceProvider` with a condition based on the `EMAIL_PROVIDER` environment variable
