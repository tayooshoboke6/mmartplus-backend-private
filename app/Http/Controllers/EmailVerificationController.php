<?php

namespace App\Http\Controllers;

use App\Services\Email\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    protected $emailVerificationService;
    
    /**
     * Create a new controller instance.
     *
     * @param EmailVerificationService $emailVerificationService
     */
    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }
    
    /**
     * Send a verification code to the authenticated user's email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/email/verify/send",
     *     summary="Send email verification code",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Verification code sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Verification code sent successfully. Please check your email.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email already verified or other error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Email is already verified.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function send(Request $request)
    {
        $user = $request->user();
        
        // Check if email is already verified
        if ($user->email_verified_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is already verified.'
            ], 400);
        }
        
        // Send verification code to email
        $this->emailVerificationService->sendVerificationEmail($user);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Verification code sent successfully. Please check your email.'
        ]);
    }
    
    /**
     * Verify the user's email with the provided code.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/email/verify",
     *     summary="Verify email with code",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Email verified successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired verification code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid or expired verification code.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        
        $user = $request->user();
        
        // Check if email is already verified
        if ($user->email_verified_at) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email is already verified.'
            ]);
        }
        
        // Verify the code
        $verified = $this->emailVerificationService->verifyEmail($user, $request->code);
        
        if (!$verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.'
            ], 400);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully.'
        ]);
    }
    
    /**
     * Check the verification status of the user's email.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Get(
     *     path="/api/email/status",
     *     summary="Get email verification status",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Email verification status",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="verified", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function status(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'verified' => $user->email_verified_at !== null,
            ]
        ]);
    }
    
    /**
     * Send a verification code to a specific email (for non-authenticated users).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/email/non-auth/send",
     *     summary="Send email verification code to a specific email (non-authenticated)",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Verification code sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Verification code sent successfully. Please check your email.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     )
     * )
     */
    public function sendNonAuth(Request $request)
    {
        // Log detailed request information for debugging CORS issues
        Log::info('Email verification non-auth request received', [
            'headers' => request()->headers->all(),
            'origin' => request()->header('Origin'),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'request_data' => $request->all()
        ]);
        
        try {
            $request->validate([
                'email' => 'required|string|email',
            ]);
            
            $user = \App\Models\User::where('email', $request->email)->first();
            
            if ($user) {
                // Check if email is already verified
                if ($user->email_verified_at) {
                    Log::info('Email already verified', ['email' => $request->email]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Email is already verified.'
                    ], 400);
                }
                
                // Send verification code to email
                $this->emailVerificationService->sendVerificationEmail($user);
                
                Log::info('Verification code sent to existing user', ['email' => $request->email]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Verification code sent successfully. Please check your email.'
                ]);
            } else {
                // Create a temporary record for verification purposes
                $tempCode = $this->emailVerificationService->generateVerificationCode();
                
                // Store the code in the email_verification table without a user_id
                \App\Models\EmailVerification::create([
                    'email' => $request->email,
                    'code' => $tempCode,
                    'expires_at' => now()->addMinutes(30),
                ]);
                
                // Send verification code to the email
                $this->emailVerificationService->sendVerificationEmailRaw($request->email, $tempCode);
                
                Log::info('Verification code sent to new email', ['email' => $request->email]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Verification code sent successfully. Please check your email.'
                ]);
            }
        } catch (\Exception $e) {
            // Log any exceptions that occur
            Log::error('Error in email verification process', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->email ?? 'not provided'
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing your request.'
            ], 500);
        }
    }
    
    /**
     * Verify an email with the provided code (for non-authenticated users).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/email/non-auth/verify",
     *     summary="Verify email with code (non-authenticated)",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "code"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Email verified successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired verification code",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Invalid or expired verification code.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not found.")
     *         )
     *     )
     * )
     */
    public function verifyNonAuth(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|string',
        ]);
        
        // Check if the email exists in the users table
        $user = \App\Models\User::where('email', $request->email)->first();
        
        if ($user) {
            // Check if email is already verified
            if ($user->email_verified_at) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Email is already verified.'
                ]);
            }
            
            // Verify the code for existing user
            $verified = $this->emailVerificationService->verifyEmail($user, $request->code);
            
            if (!$verified) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired verification code.'
                ], 400);
            }
            
            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully.'
            ]);
        } else {
            // Check for verification in the email_verification table for users that don't exist yet
            $verification = \App\Models\EmailVerification::where('email', $request->email)
                ->where('code', $request->code)
                ->where('expires_at', '>', now())
                ->where('is_used', false)
                ->first();
            
            if (!$verification) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid or expired verification code.'
                ], 400);
            }
            
            // Mark the code as used
            $verification->update(['is_used' => true]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully.'
            ]);
        }
    }
}
