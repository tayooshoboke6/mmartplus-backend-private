<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * This is an example implementation of a local SMS service provider.
 * Replace with the actual implementation for your chosen SMS provider.
 */
class LocalSMSService implements SMSServiceInterface
{
    protected $apiKey;
    protected $apiSecret;
    protected $senderId;
    protected $baseUrl = 'https://example-sms-provider.com/api';

    public function __construct()
    {
        $this->apiKey = config('services.sms.api_key');
        $this->apiSecret = config('services.sms.api_secret');
        $this->senderId = config('services.sms.sender_id');
    }

    /**
     * Send an SMS message to a phone number.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message to send
     * @return bool Whether the message was sent successfully
     */
    public function send(string $phoneNumber, string $message): bool
    {
        try {
            // Format the phone number if needed
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Log the attempt
            Log::info("Attempting to send SMS to {$formattedPhone}");
            
            // Make the API request to the SMS provider
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send', [
                'from' => $this->senderId,
                'to' => $formattedPhone,
                'message' => $message,
                'api_secret' => $this->apiSecret,
            ]);
            
            // Check if successful and log the result
            if ($response->successful()) {
                Log::info("SMS sent successfully to {$formattedPhone}");
                return true;
            } else {
                Log::error("SMS sending failed: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("SMS sending exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format the phone number to the required format for the SMS provider.
     * This method should be customized based on the specific provider requirements.
     *
     * @param string $phoneNumber The original phone number
     * @return string The formatted phone number
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Add country code if not present (assuming Nigerian numbers here, customize as needed)
        if (strlen($cleaned) <= 10) {
            return '234' . ltrim($cleaned, '0');
        }
        
        return $cleaned;
    }
}
