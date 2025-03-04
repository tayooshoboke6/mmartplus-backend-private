<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SMS\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
{
    /**
     * @var VerificationService
     */
    protected $verificationService;
    
    /**
     * Create a new controller instance.
     *
     * @param VerificationService $verificationService
     */
    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
        $this->middleware('auth:sanctum');
    }
    
    /**
     * Send verification code to the authenticated user's phone.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/phone/verify/send",
     *     summary="Send verification code to the authenticated user's phone",
     *     tags={"Phone Verification"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Verification code sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Verification code sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Phone number not provided or already verified"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to send verification code"
     *     )
     * )
     */
    public function send(Request $request)
    {
        $user = $request->user();
        
        // Check if phone number exists
        if (!$user->phone_number) {
            return response()->json([
                'status' => 'error',
                'message' => 'No phone number provided for this user.'
            ], 400);
        }
        
        // Check if phone is already verified
        if ($user->phone_verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Phone number is already verified.'
            ], 400);
        }
        
        // Generate and send verification code
        $verificationCode = $this->verificationService->generate($user);
        $sent = $this->verificationService->send($verificationCode);
        
        if (!$sent) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification code. Please try again later.'
            ], 500);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Verification code sent successfully. Please check your phone.'
        ]);
    }
    
    /**
     * Verify the phone number with the provided code.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 
     * @OA\Post(
     *     path="/api/phone/verify",
     *     summary="Verify the user's phone number with the provided code",
     *     tags={"Phone Verification"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Phone number verified successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Phone number verified successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired verification code"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);
        
        $user = $request->user();
        
        // Check if phone number exists
        if (!$user->phone_number) {
            return response()->json([
                'status' => 'error',
                'message' => 'No phone number provided for this user.'
            ], 400);
        }
        
        // Check if phone is already verified
        if ($user->phone_verified) {
            return response()->json([
                'status' => 'success',
                'message' => 'Phone number is already verified.'
            ]);
        }
        
        // Verify the code
        $verified = $this->verificationService->verify($user, $request->code);
        
        if (!$verified) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or expired verification code.'
            ], 400);
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Phone number verified successfully.'
        ]);
    }
}
