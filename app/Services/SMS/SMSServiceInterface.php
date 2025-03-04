<?php

namespace App\Services\SMS;

interface SMSServiceInterface
{
    /**
     * Send an SMS message to a phone number.
     *
     * @param string $phoneNumber The recipient's phone number
     * @param string $message The message content
     * @return bool Whether the message was sent successfully
     */
    public function send(string $phoneNumber, string $message): bool;
}
