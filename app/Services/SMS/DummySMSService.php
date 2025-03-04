<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Log;

class DummySMSService implements SMSServiceInterface
{
    /**
     * Send an SMS message to a phone number.
     * This is a dummy implementation that just logs the message.
     * Will be replaced with actual SMS provider implementation.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message content
     * @return bool Whether the message was sent successfully
     */
    public function send(string $phoneNumber, string $message): bool
    {
        // Log the message for development
        Log::info('SMS would be sent to ' . $phoneNumber . ': ' . $message);
        
        // In development, always return true for testing
        return true;
    }
}
