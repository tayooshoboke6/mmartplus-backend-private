<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     *
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/categories",
     *     summary="List all categories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Electronics"),
     *                     @OA\Property(property="slug", type="string", example="electronics"),
     *                     @OA\Property(property="description", type="string", example="Electronic products and gadgets"),
     *                     @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(
     *                         property="children",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="name", type="string", example="Smartphones"),
     *                             @OA\Property(property="slug", type="string", example="smartphones"),
     *                             @OA\Property(property="description", type="string", example="Smartphone devices"),
     *                             @OA\Property(property="parent_id", type="integer", example=1)
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Post(
     *     path="/api/admin/categories",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Laptops"),
     *             @OA\Property(property="description", type="string", example="Laptop computers"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="color", type="string", example="#ffffff"),
     *             @OA\Property(property="order", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="image", type="string", format="binary", example="image/jpeg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Category created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=3),
     *                 @OA\Property(property="name", type="string", example="Laptops"),
     *                 @OA\Property(property="slug", type="string", example="laptops"),
     *                 @OA\Property(property="description", type="string", example="Laptop computers"),
     *                 @OA\Property(property="parent_id", type="integer", example=1),
     *                 @OA\Property(property="color", type="string", example="#ffffff"),
     *                 @OA\Property(property="image_url", type="string", example="storage/categories/1643723400.jpg"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
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
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'color' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        
        // Check if a slug derived from the name would be unique
        $proposedSlug = Str::slug($request->name);
        $slugExists = Category::where('slug', $proposedSlug)->exists();
        if ($slugExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'A category with a similar name already exists, resulting in a duplicate slug. Please use a different name.',
                'errors' => [
                    'name' => ['This name generates a slug that is already in use.']
                ]
            ], 422);
        }
        
        // Handle image upload if present
        $imageUrl = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/categories', $filename);
            $imageUrl = 'storage/categories/' . $filename;
        }
        
        $category = Category::create([
            'name' => $request->name,
            'slug' => $proposedSlug,
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'color' => $request->color,
            'image_url' => $imageUrl,
            'order' => $request->order ?? 0,
            'is_active' => $request->is_active ?? true,
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="Get category details",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Electronics"),
     *                 @OA\Property(property="slug", type="string", example="electronics"),
     *                 @OA\Property(property="description", type="string", example="Electronic products and gadgets"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="children",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="slug", type="string")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="products",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="price", type="number")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found."),
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $category = Category::with(['children', 'products'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    /**
     * Update the specified category.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Put(
     *     path="/api/admin/categories/{id}",
     *     summary="Update a category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Electronics"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Category updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Electronics"),
     *                 @OA\Property(property="slug", type="string", example="updated-electronics"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="parent_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Category cannot be its own parent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $request->validate([
            'name' => "required|string|max:255|unique:categories,name,{$id}",
            'description' => 'nullable|string',
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($id) {
                    if ($value == $id) {
                        $fail('A category cannot be its own parent.');
                    }
                },
                'exists:categories,id'
            ],
            'color' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        
        // Check if a slug derived from the name would be unique (excluding current category)
        $proposedSlug = Str::slug($request->name);
        $slugExists = Category::where('slug', $proposedSlug)
                             ->where('id', '!=', $id)
                             ->exists();
        if ($slugExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'A category with a similar name already exists, resulting in a duplicate slug. Please use a different name.',
                'errors' => [
                    'name' => ['This name generates a slug that is already in use.']
                ]
            ], 422);
        }
        
        // Handle image upload if present
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Delete old image if exists
            if ($category->image_url && file_exists(public_path($category->image_url))) {
                unlink(public_path($category->image_url));
            }
            
            $image = $request->file('image');
            $filename = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/categories', $filename);
            $category->image_url = 'storage/categories/' . $filename;
        }
        
        $category->name = $request->name;
        $category->slug = $proposedSlug;
        $category->description = $request->description;
        $category->parent_id = $request->parent_id;
        $category->color = $request->color;
        $category->order = $request->order ?? $category->order;
        $category->is_active = $request->has('is_active') ? $request->is_active : $category->is_active;
        $category->save();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified category.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Delete(
     *     path="/api/admin/categories/{id}",
     *     summary="Delete a category",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Category deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Cannot delete category with products. Remove or reassign products first.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete category with products. Remove or reassign products first.'
            ], 400);
        }
        
        // Reassign children categories to parent
        if ($category->children()->count() > 0) {
            $category->children()->update(['parent_id' => $category->parent_id]);
        }
        
        $category->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Category deleted successfully'
        ]);
    }
}
