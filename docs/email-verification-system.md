# M-Mart+ Email Verification System

## Overview

M-Mart+ includes a comprehensive email verification system that enables users to verify their email addresses during registration and receive important notifications about their orders. This document provides an overview of the system architecture, configuration options, and implementation details.

## Features

### Email Verification
- Automatic verification code generation and delivery during user registration
- Secure code validation with expiration handling (codes expire after 30 minutes)
- Support for both authenticated and non-authenticated verification flows
- Prevention of login attempts from unverified accounts

### Email Notifications
- Order confirmation emails when users place orders
- Order status update notifications when order status changes
- Welcome emails for new users after successful registration
- Support for promotional campaigns and marketing emails

## Technical Implementation

### Email Service Architecture
The email system follows a provider-agnostic design with interface-based implementation, allowing for easy switching between different email service providers:

- `EmailServiceInterface`: Defines the contract that all email service implementations must follow
- `BrevoEmailService`: Implementation using Brevo (formerly Sendinblue) API
- `DummyEmailService`: Implementation for development/testing environments without sending actual emails

### Controllers and Routes
- `EmailVerificationController`: Handles email verification requests
  - `send()`: Sends verification code to authenticated users
  - `verify()`: Verifies email with provided code for authenticated users
  - `status()`: Checks email verification status for authenticated users
  - `sendNonAuth()`: Sends verification code for non-authenticated users
  - `verifyNonAuth()`: Verifies email for non-authenticated users

### API Endpoints
- `/api/email/verify/send` (POST, authenticated): Send verification code to current user
- `/api/email/verify` (POST, authenticated): Verify email with code for current user
- `/api/email/status` (GET, authenticated): Check verification status for current user
- `/api/email/non-auth/send` (POST): Send verification code to specified email (non-authenticated)
- `/api/email/non-auth/verify` (POST): Verify email with code (non-authenticated)

### Frontend Implementation
- `RegistrationVerificationPage`: React component for email verification after registration
- `emailVerificationService`: Service for handling API calls to verification endpoints

### Email Templates
Beautiful, responsive HTML email templates are provided for all email types:
- `verify_email.blade.php`: Template for verification emails
- `welcome.blade.php`: Template for welcome emails
- `order_confirmation.blade.php`: Template for order confirmations
- `order_status_update.blade.php`: Template for order status updates

## Configuration

Email functionality is configured via environment variables in the `.env` file:

```
# Email Provider Configuration
EMAIL_PROVIDER=brevo     # Options: 'brevo' or 'dummy'

# Brevo Configuration (if using Brevo provider)
BREVO_API_KEY=your-api-key-here
BREVO_FROM_EMAIL=noreply@example.com
BREVO_FROM_NAME="M-Mart+"
```

## Usage Examples

### Sending a Verification Email
```php
// In a controller
public function registerUser(Request $request)
{
    // Create the user
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    // Send verification email
    $this->emailVerificationService->sendVerificationEmail($user);

    return response()->json([
        'message' => 'User registered. Please check your email for verification.'
    ]);
}
```

### Frontend Verification Flow
```javascript
// In React component
const handleVerification = async () => {
    try {
        // Send verification code
        await emailVerificationService.sendVerificationCodeByEmail(email);
        
        // User enters code in UI
        
        // Verify email with entered code
        await emailVerificationService.verifyEmailWithCode(email, code);
        
        // Redirect to login
        navigate('/login');
    } catch (error) {
        setError(error.message);
    }
};
```

## Testing

The email verification system includes comprehensive tests:
- Unit tests for the EmailVerificationService
- Feature tests for the EmailVerificationController
- Integration tests for the complete verification flow

## Development vs. Production

- **Development**: Use the 'dummy' provider to avoid sending real emails during development
- **Production**: Use the 'brevo' provider with your Brevo API key for production environments

## Troubleshooting

- If verification emails are not being received, check the BREVO_API_KEY and BREVO_FROM_EMAIL settings
- For local development, check that the EMAIL_PROVIDER is set to 'dummy'
- If verification codes are expiring too quickly, the 30-minute expiration time can be adjusted in the code
