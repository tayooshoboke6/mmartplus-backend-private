<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRating;
use App\Models\Order;
use App\Services\RatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductRatingController extends Controller
{
    /**
     * Rating service instance.
     *
     * @var \App\Services\RatingService
     */
    protected $ratingService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\RatingService  $ratingService
     * @return void
     */
    public function __construct(RatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    /**
     * Display ratings for a specific product.
     *
     * @param  int  $productId
     * @return \Illuminate\Http\Response
     */
    public function getProductRatings($productId)
    {
        $product = Product::findOrFail($productId);
        $ratings = $product->ratings()
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return response()->json([
            'status' => 'success',
            'data' => [
                'average_rating' => $product->average_rating,
                'bayesian_rating' => $product->bayesian_rating,
                'rating_count' => $product->rating_count,
                'ratings' => $ratings
            ]
        ]);
    }
    
    /**
     * Store a new rating for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $productId
     * @return \Illuminate\Http\Response
     */
    public function rateProduct(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000'
        ]);
        
        $user = Auth::user();
        $product = Product::findOrFail($productId);
        
        // Check if user is admin or has purchased the product
        $isAdmin = $user->hasRole(['admin', 'super-admin']);
        $hasPurchased = Order::where('user_id', $user->id)
            ->whereHas('orderItems', function($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('status', 'completed')
            ->exists();
            
        if (!$isAdmin && !$hasPurchased) {
            return response()->json([
                'status' => 'error',
                'message' => 'You must purchase this product before rating it'
            ], 403);
        }
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Check if user already rated this product
            $existingRating = ProductRating::where('product_id', $productId)
                ->where('user_id', $user->id)
                ->first();
                
            if ($existingRating) {
                // Update existing rating
                $oldRating = $existingRating->rating;
                $existingRating->update([
                    'rating' => $request->rating,
                    'review' => $request->review
                ]);
                
                // Update product average rating
                $this->ratingService->updateProductRating($product, $oldRating, $request->rating, false);
                
                $message = 'Rating updated successfully';
            } else {
                // Create new rating
                ProductRating::create([
                    'product_id' => $productId,
                    'user_id' => $user->id,
                    'rating' => $request->rating,
                    'review' => $request->review,
                    'verified_purchase' => $hasPurchased
                ]);
                
                // Update product average rating
                $this->ratingService->updateProductRating($product, 0, $request->rating, true);
                
                $message = 'Rating added successfully';
            }
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => [
                    'average_rating' => $product->average_rating,
                    'bayesian_rating' => $product->bayesian_rating,
                    'rating_count' => $product->rating_count
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to rate product: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update product ratings directly (admin only)
     * 
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminAdjustRating(Request $request, $productId)
    {
        // Validate request
        $validated = $request->validate([
            'average_rating' => 'required|numeric|min:0|max:5',
            'bayesian_rating' => 'required|numeric|min:0|max:5',
            'rating_count' => 'required|integer|min:0',
        ]);

        // Find the product
        $product = Product::findOrFail($productId);
        
        // Update ratings directly
        $product->update([
            'average_rating' => $validated['average_rating'],
            'bayesian_rating' => $validated['bayesian_rating'],
            'rating_count' => $validated['rating_count'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product ratings updated successfully',
            'product' => $product->fresh()
        ]);
    }
    
    /**
     * Delete a rating.
     *
     * @param  int  $ratingId
     * @return \Illuminate\Http\Response
     */
    public function deleteRating($ratingId)
    {
        $user = Auth::user();
        $rating = ProductRating::findOrFail($ratingId);
        
        // Only allow users to delete their own ratings or admins to delete any
        if ($rating->user_id !== $user->id && !$user->hasRole(['admin', 'super-admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not authorized to delete this rating'
            ], 403);
        }
        
        $product = $rating->product;
        $ratingValue = $rating->rating;
        
        DB::beginTransaction();
        
        try {
            // Delete the rating
            $rating->delete();
            
            // Update the product's average rating
            $this->ratingService->updateProductRating($product, $ratingValue, 0, false);
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Rating deleted successfully',
                'data' => [
                    'average_rating' => $product->average_rating,
                    'bayesian_rating' => $product->bayesian_rating,
                    'rating_count' => $product->rating_count
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete rating: ' . $e->getMessage()
            ], 500);
        }
    }
}
