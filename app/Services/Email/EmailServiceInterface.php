<?php

namespace App\Services\Email;

interface EmailServiceInterface
{
    /**
     * Send an email message.
     *
     * @param string $to The recipient's email
     * @param string $subject The subject of the email
     * @param string $content The email content (HTML)
     * @param string|null $fromName The sender's name
     * @param string|null $replyTo The reply-to email address
     * @return bool Whether the email was sent successfully
     */
    public function send(string $to, string $subject, string $content, ?string $fromName = null, ?string $replyTo = null): bool;
}
