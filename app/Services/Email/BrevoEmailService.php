<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoEmailService implements EmailServiceInterface
{
    protected $apiKey;
    protected $fromEmail;
    protected $baseUrl = 'https://api.brevo.com/v3';

    public function __construct()
    {
        $this->apiKey = config('services.brevo.api_key');
        $this->fromEmail = config('services.brevo.from_email');
    }

    /**
     * Send an email message using Brevo.
     *
     * @param string $to The recipient's email
     * @param string $subject The subject of the email
     * @param string $content The email content (HTML)
     * @param string|null $fromName The sender's name
     * @param string|null $replyTo The reply-to email address
     * @return bool Whether the email was sent successfully
     */
    public function send(string $to, string $subject, string $content, ?string $fromName = null, ?string $replyTo = null): bool
    {
        try {
            // Log the attempt
            Log::info("Attempting to send email to {$to}");
            
            // Prepare the request data
            $data = [
                'sender' => [
                    'email' => $this->fromEmail,
                    'name' => $fromName ?? config('app.name'),
                ],
                'to' => [
                    [
                        'email' => $to,
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $content,
            ];
            
            // Add reply-to if provided
            if ($replyTo) {
                $data['replyTo'] = ['email' => $replyTo];
            }
            
            // Make the API request to Brevo
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])->post($this->baseUrl . '/smtp/email', $data);
            
            // Check if successful and log the result
            if ($response->successful()) {
                Log::info("Email sent successfully to {$to}");
                return true;
            } else {
                Log::error("Email sending failed: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Email sending exception: " . $e->getMessage());
            return false;
        }
    }
}
