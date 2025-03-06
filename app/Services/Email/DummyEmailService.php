<?php

namespace App\Services\Email;

use Illuminate\Support\Facades\Log;

class DummyEmailService implements EmailServiceInterface
{
    /**
     * Send an email message (logs it instead of actually sending).
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
        Log::info("DUMMY EMAIL SERVICE - Would have sent an email to {$to}");
        Log::info("Subject: {$subject}");
        Log::info("From: " . ($fromName ?? config('app.name')));
        if ($replyTo) {
            Log::info("Reply-To: {$replyTo}");
        }
        Log::info("Content: " . substr(strip_tags($content), 0, 100) . '...');
        
        return true;
    }
}
