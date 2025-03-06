<?php

namespace App\Services\SMS;

use App\Models\User;
use App\Models\VerificationCode;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VerificationService
{
    /**
     * @var SMSServiceInterface
     */
    protected $smsService;
    
    /**
     * Number of minutes until the verification code expires.
     */
    const EXPIRATION_TIME = 15;
    
    /**
     * Length of the verification code.
     */
    const CODE_LENGTH = 6;
    
    /**
     * Create a new verification service instance.
     *
     * @param SMSServiceInterface $smsService
     */
    public function __construct(SMSServiceInterface $smsService)
    {
        $this->smsService = $smsService;
    }
    
    /**
     * Generate a new verification code for a user.
     *
     * @param User $user
     * @return VerificationCode
     */
    public function generate(User $user): VerificationCode
    {
        // Expire any existing verification codes for this user
        VerificationCode::where('user_id', $user->id)
            ->where('is_used', false)
            ->update(['is_used' => true]);
        
        // Generate a new code
        $code = $this->generateRandomCode();
        $expiresAt = Carbon::now()->addMinutes(self::EXPIRATION_TIME);
        
        // Create and return the verification code
        return VerificationCode::create([
            'user_id' => $user->id,
            'phone_number' => $user->phone_number,
            'code' => $code,
            'expires_at' => $expiresAt,
            'is_used' => false,
        ]);
    }
    
    /**
     * Send the verification code to the user's phone number.
     *
     * @param VerificationCode $verificationCode
     * @return bool
     */
    public function send(VerificationCode $verificationCode): bool
    {
        $message = "Your M-Mart+ verification code is: {$verificationCode->code}. This code will expire in " . self::EXPIRATION_TIME . " minutes.";
        
        return $this->smsService->send($verificationCode->phone_number, $message);
    }
    
    /**
     * Verify the code matches and is valid.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function verify(User $user, string $code): bool
    {
        $verificationCode = VerificationCode::where('user_id', $user->id)
            ->where('phone_number', $user->phone_number)
            ->where('code', $code)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
        
        if (!$verificationCode) {
            return false;
        }
        
        // Mark code as used
        $verificationCode->markAsUsed();
        
        // Mark user's phone as verified
        $user->update(['phone_verified' => true]);
        
        return true;
    }
    
    /**
     * Generate a random verification code.
     *
     * @return string
     */
    protected function generateRandomCode(): string
    {
        return (string) rand(100000, 999999);
    }
}
