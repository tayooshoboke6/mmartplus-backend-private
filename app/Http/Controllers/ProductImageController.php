<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    protected $fileUploadService;
    
    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\FileUploadService  $fileUploadService
     * @return void
     */
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }
    
    /**
     * Upload a new image for a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Post(
     *     path="/api/products/{product_id}/images",
     *     summary="Upload product image",
     *     description="Upload a new image for a product. Admin only.",
     *     tags={"Product Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="file",
     *                     description="Product image file"
     *                 ),
     *                 @OA\Property(
     *                     property="is_primary",
     *                     type="boolean",
     *                     description="Set as primary image"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Image uploaded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="product_id", type="integer", example=1),
     *                 @OA\Property(property="image_path", type="string", example="products/abc123.jpg"),
     *                 @OA\Property(property="is_primary", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="image",
     *                     type="array",
     *                     @OA\Items(type="string", example="The image field is required.")
     *                 )
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
    public function upload(Request $request, $product_id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        $product = Product::findOrFail($product_id);
        
        // Store the uploaded image
        $path = $this->fileUploadService->store(
            $request->file('image'),
            'products',
            Str::slug($product->name) . '-' . uniqid() . '.' . $request->file('image')->getClientOriginalExtension()
        );
        
        // Add the image path to the product's images array
        $images = $product->images ?? [];
        $images[] = [
            'path' => $path,
            'url' => $this->fileUploadService->getUrl($path),
            'is_primary' => count($images) === 0, // First image is primary by default
        ];
        
        $product->update(['images' => $images]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Image uploaded successfully',
            'data' => $product->fresh()
        ]);
    }
    
    /**
     * Remove a product image.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Delete(
     *     path="/api/product-images/{id}",
     *     summary="Delete product image",
     *     description="Remove a product image. Admin only.",
     *     tags={"Product Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Image deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found")
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
    public function remove(Request $request, $id)
    {
        $request->validate([
            'image_index' => 'required|integer|min:0',
        ]);
        
        $product = Product::findOrFail($id);
        $images = $product->images ?? [];
        
        if (!isset($images[$request->image_index])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image not found'
            ], 404);
        }
        
        // Get the image to remove
        $imageToRemove = $images[$request->image_index];
        
        // Delete the file from storage
        $this->fileUploadService->delete($imageToRemove['path']);
        
        // Remove the image from the array
        array_splice($images, $request->image_index, 1);
        
        // If it was the primary image and we have other images, set a new primary
        if (isset($imageToRemove['is_primary']) && $imageToRemove['is_primary'] && count($images) > 0) {
            $images[0]['is_primary'] = true;
        }
        
        $product->update(['images' => $images]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Image removed successfully',
            'data' => $product->fresh()
        ]);
    }
    
    /**
     * Set an image as the primary image for a product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Patch(
     *     path="/api/product-images/{id}/primary",
     *     summary="Set primary image",
     *     description="Set an image as the primary image for a product. Admin only.",
     *     tags={"Product Images"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Image ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Primary image set successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Primary image set successfully"),
     *             @OA\Property(
     *                 property="data", 
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Image not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found")
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
    public function setPrimary(Request $request, $id)
    {
        $request->validate([
            'image_index' => 'required|integer|min:0',
        ]);
        
        $product = Product::findOrFail($id);
        $images = $product->images ?? [];
        
        if (!isset($images[$request->image_index])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Image not found'
            ], 404);
        }
        
        // Update primary status for all images
        foreach ($images as $key => $image) {
            $images[$key]['is_primary'] = $key === (int) $request->image_index;
        }
        
        $product->update(['images' => $images]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Primary image updated successfully',
            'data' => $product->fresh()
        ]);
    }
}
