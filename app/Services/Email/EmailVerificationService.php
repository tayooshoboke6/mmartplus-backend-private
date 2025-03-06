<?php

namespace App\Services\Email;

use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class EmailVerificationService
{
    protected $emailService;

    /**
     * Create a new EmailVerificationService instance.
     *
     * @param EmailServiceInterface $emailService
     */
    public function __construct(EmailServiceInterface $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Generate a verification code for the user and send it via email.
     *
     * @param User $user
     * @return VerificationCode
     */
    public function sendVerificationEmail(User $user): VerificationCode
    {
        // Create a new verification code
        $verificationCode = $this->generateCode($user);
        
        // Create the email content
        $subject = 'Verify Your Email Address - ' . config('app.name');
        $content = $this->getEmailVerificationTemplate($user, $verificationCode->code);
        
        // Send the email
        $this->emailService->send(
            $user->email,
            $subject,
            $content,
            config('app.name'),
            null
        );
        
        return $verificationCode;
    }
    
    /**
     * Generate a new verification code for a user.
     *
     * @param User $user
     * @return VerificationCode
     */
    public function generateCode(User $user): VerificationCode
    {
        // Delete any existing unused codes for this user's email
        VerificationCode::where('user_id', $user->id)
            ->where('phone_number', null)
            ->where('is_used', false)
            ->delete();
        
        // Generate a new 6-digit code
        $code = (string) random_int(100000, 999999);
        
        // Create and return a new verification code
        return VerificationCode::create([
            'user_id' => $user->id,
            'phone_number' => null, // null to indicate it's for email, not phone
            'code' => $code,
            'expires_at' => Carbon::now()->addMinutes(30),
            'is_used' => false,
        ]);
    }
    
    /**
     * Verify a user's email with the given code.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verifyEmail(User $user, string $code): bool
    {
        // Find the verification code for this user
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('phone_number', null)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
        
        if (!$verificationCode) {
            return false;
        }
        
        // Mark the code as used
        $verificationCode->update(['is_used' => true]);
        
        // Mark the user's email as verified
        $user->email_verified_at = Carbon::now();
        $user->save();
        
        return true;
    }
    
    /**
     * Get the email verification template.
     *
     * @param User $user
     * @param string $code
     * @return string
     */
    private function getEmailVerificationTemplate(User $user, string $code): string
    {
        // Use the Blade template with view rendering
        return view('emails.verify_email', [
            'user' => $user,
            'code' => $code
        ])->render();
    }
}
