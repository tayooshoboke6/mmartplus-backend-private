<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialAuthController extends Controller
{
    /**
     * Redirect to social provider for authentication.
     *
     * @param string $provider The social provider (google, apple)
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/auth/{provider}/redirect",
     *     summary="Redirect to social authentication provider",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Social provider (google, apple)",
     *         @OA\Schema(type="string", enum={"google", "apple"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Redirect URL for social authentication",
     *         @OA\JsonContent(
     *             @OA\Property(property="redirect_url", type="string", example="https://accounts.google.com/o/oauth2/auth?client_id=...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid provider"
     *     )
     * )
     */
    public function redirectToProvider($provider)
    {
        try {
            if (!in_array($provider, ['google', 'apple'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid provider',
                ], 400);
            }
            
            // For API usage, return the redirect URL rather than redirecting
            $redirectUrl = Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
            
            return response()->json([
                'status' => 'success',
                'redirect_url' => $redirectUrl
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not connect to ' . $provider . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle callback from social provider.
     *
     * @param Request $request
     * @param string $provider The social provider (google, apple)
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/auth/{provider}/callback",
     *     summary="Handle social authentication callback",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="Social provider (google, apple)",
     *         @OA\Schema(type="string", enum={"google", "apple"})
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="Authorization code from provider",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User authenticated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Authenticated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                     @OA\Property(property="avatar", type="string", example="https://lh3.googleusercontent.com/..."),
     *                     @OA\Property(
     *                         property="roles",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="customer")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid provider or request"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Authentication error"
     *     )
     * )
     */
    public function handleProviderCallback(Request $request, $provider)
    {
        try {
            if (!in_array($provider, ['google', 'apple'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid provider',
                ], 400);
            }
            
            $socialUser = Socialite::driver($provider)->stateless()->user();
            
            // Find existing user or create new one
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'avatar' => $socialUser->getAvatar(),
                    'social_id' => $socialUser->getId(),
                    'social_type' => $provider,
                    'password' => null, // No password for social login
                ]);
                
                // Assign customer role by default
                $user->assignRole('customer');
            } else {
                // Update existing user with social info
                $user->update([
                    'social_id' => $socialUser->getId(),
                    'social_type' => $provider,
                    'avatar' => $socialUser->getAvatar() ?: $user->avatar,
                ]);
            }
            
            // Generate token
            $token = $user->createToken('auth_token')->plainTextToken;
            
            // Load the roles relation
            $user->load('roles:id,name');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Authenticated successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
