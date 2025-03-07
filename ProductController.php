<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/products",
     *     summary="List all products",
     *     description="Get a paginated list of products with optional filtering",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filter products by category ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price filter",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price filter",
     *         required=false,
     *         @OA\Schema(type="number")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for product name or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", enum={"created_at", "price", "name"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Sort direction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=12)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of products",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="iPhone 13"),
     *                         @OA\Property(property="description", type="string", example="Latest iPhone model"),
     *                         @OA\Property(property="price", type="number", format="float", example=999.99),
     *                         @OA\Property(property="stock", type="integer", example=50),
     *                         @OA\Property(property="sku", type="string", example="IPHONE13256"),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="category_id", type="integer", example=1),
     *                         @OA\Property(
     *                             property="category",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Smartphones")
     *                         ),
     *                         @OA\Property(
     *                             property="images",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="path", type="string"),
     *                                 @OA\Property(property="url", type="string"),
     *                                 @OA\Property(property="is_primary", type="boolean")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Product::with('category')->where('is_active', true);
        
        // Filter by category if specified
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Filter by price range if specified
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        
        // Search by name or description if search term is provided
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }
        
        // Sort products
        if ($request->has('sort_by')) {
            $sortDirection = $request->has('sort_direction') ? $request->sort_direction : 'asc';
            $query->orderBy($request->sort_by, $sortDirection);
        } else {
            $query->latest();
        }
        
        $products = $query->paginate($request->per_page ?? 12);
        
        // Transform products to ensure proper image formatting
        $transformedProducts = $products->getCollection()->map(function ($product) {
            // Make sure images field is properly formatted as array
            if (is_string($product->images)) {
                $product->images = json_decode($product->images, true);
            }
            
            // Ensure stock is provided correctly
            $product->stock = $product->stock;
            
            return $product;
        });
        
        // Replace the collection with our transformed collection
        $products->setCollection($transformedProducts);
        
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * 
     * @OA\Post(
     *     path="/api/admin/products",
     *     summary="Create a new product",
     *     description="Create a new product with the provided details",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "price", "stock", "category_id"},
     *             @OA\Property(property="name", type="string", example="iPhone 14 Pro"),
     *             @OA\Property(property="description", type="string", example="Latest iPhone model with advanced features"),
     *             @OA\Property(property="price", type="number", format="float", example=1299.99),
     *             @OA\Property(property="stock", type="integer", example=100),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="path", type="string"),
     *                     @OA\Property(property="url", type="string"),
     *                     @OA\Property(property="is_primary", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="iPhone 14 Pro"),
     *                 @OA\Property(property="description", type="string", example="Latest iPhone model with advanced features"),
     *                 @OA\Property(property="price", type="number", format="float", example=1299.99),
     *                 @OA\Property(property="stock", type="integer", example=100),
     *                 @OA\Property(property="sku", type="string", example="IPHONE14PRO"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Smartphones")
     *                 ),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="path", type="string"),
     *                         @OA\Property(property="url", type="string"),
     *                         @OA\Property(property="is_primary", type="boolean")
     *                     )
     *                 ),
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
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field is required.")),
     *                 @OA\Property(property="price", type="array", @OA\Items(type="string", example="The price must be at least 0.")),
     *                 @OA\Property(property="stock", type="array", @OA\Items(type="string", example="The stock must be at least 0.")),
     *                 @OA\Property(property="category_id", type="array", @OA\Items(type="string", example="The selected category id is invalid."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'expiry_date' => 'nullable|date',
            'delivery_time' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|max:2048',
        ]);
        
        // Generate a unique SKU if not provided
        $sku = $request->sku ?? Str::upper(Str::random(8));
        while (Product::where('sku', $sku)->exists()) {
            $sku = Str::upper(Str::random(8));
        }
        
        // Generate slug if not provided
        $slug = $request->slug ?? Str::slug($request->name);
        
        // Handle image uploads
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('products', 'public');
                $images[] = asset('storage/' . $imagePath);
            }
        }
        
        $product = Product::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'stock' => $request->stock,
            'sku' => $sku,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
            'category_id' => $request->category_id,
            'expiry_date' => $request->expiry_date,
            'delivery_time' => $request->delivery_time,
            'images' => json_encode($images),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $product->load('category')
        ], 201);
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/products/{id}",
     *     summary="Get product details",
     *     description="Get detailed information about a specific product",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="iPhone 13"),
     *                 @OA\Property(property="description", type="string", example="Latest iPhone model"),
     *                 @OA\Property(property="price", type="number", format="float", example=999.99),
     *                 @OA\Property(property="stock", type="integer", example=50),
     *                 @OA\Property(property="sku", type="string", example="IPHONE13256"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Smartphones")
     *                 ),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="path", type="string"),
     *                         @OA\Property(property="url", type="string"),
     *                         @OA\Property(property="is_primary", type="boolean")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Display the specified product by slug.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/products/slug/{slug}",
     *     summary="Get product details by slug",
     *     description="Get detailed information about a specific product using its slug",
     *     tags={"Products"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Product slug",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Organic Fresh Tomatoes"),
     *                 @OA\Property(property="slug", type="string", example="organic-fresh-tomatoes"),
     *                 @OA\Property(property="description", type="string", example="Fresh organic tomatoes"),
     *                 @OA\Property(property="price", type="number", format="float", example=3.99),
     *                 @OA\Property(property="stock", type="integer", example=50),
     *                 @OA\Property(property="sku", type="string", example="ORG-TOMATO-001"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=3),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=3),
     *                     @OA\Property(property="name", type="string", example="Vegetables")
     *                 ),
     *                 @OA\Property(
     *                     property="images",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="path", type="string"),
     *                         @OA\Property(property="url", type="string"),
     *                         @OA\Property(property="is_primary", type="boolean")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product not found")
     *         )
     *     )
     * )
     */
    public function showBySlug($slug)
    {
        // Optimize query with index on slug field
        $product = Product::with('category')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
            
        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
        
        // Ensure images are properly formatted
        if (is_string($product->images)) {
            $product->images = json_decode($product->images, true);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $product
        ]);
    }

    /**
     * Update the specified product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Put(
     *     path="/api/admin/products/{id}",
     *     summary="Update a product",
     *     description="Update an existing product's details",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated iPhone 14 Pro"),
     *             @OA\Property(property="description", type="string", example="Updated product description"),
     *             @OA\Property(property="price", type="number", format="float", example=1199.99),
     *             @OA\Property(property="stock", type="integer", example=150),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(
     *                 property="images",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="path", type="string"),
     *                     @OA\Property(property="url", type="string"),
     *                     @OA\Property(property="is_primary", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Product updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated iPhone 14 Pro"),
     *                 @OA\Property(property="description", type="string", example="Updated product description"),
     *                 @OA\Property(property="price", type="number", format="float", example=1199.99),
     *                 @OA\Property(property="stock", type="integer", example=150),
     *                 @OA\Property(property="sku", type="string", example="IPHONE14PRO"),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(
     *                     property="category",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Smartphones")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found.")
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
     *                 @OA\Property(property="name", type="array", @OA\Items(type="string", example="The name field must be a string.")),
     *                 @OA\Property(property="price", type="array", @OA\Items(type="string", example="The price must be at least 0.")),
     *                 @OA\Property(property="stock", type="array", @OA\Items(type="string", example="The stock must be at least 0.")),
     *                 @OA\Property(property="category_id", type="array", @OA\Items(type="string", example="The selected category id is invalid."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'expiry_date' => 'nullable|date',
            'delivery_time' => 'nullable|string|max:255',
            'images.*' => 'nullable|image|max:2048',
            'existing_images' => 'nullable|json',
        ]);
        
        // Generate slug if not provided
        $slug = $request->slug ?? Str::slug($request->name);
        
        // Handle image uploads and existing images
        $existingImages = [];
        if ($request->existing_images) {
            $existingImages = json_decode($request->existing_images, true);
        } else if (is_string($product->images)) {
            $existingImages = json_decode($product->images, true) ?? [];
        }
        
        // Add new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = $image->store('products', 'public');
                $existingImages[] = asset('storage/' . $imagePath);
            }
        }
        
        $product->update([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'stock' => $request->stock,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
            'category_id' => $request->category_id,
            'expiry_date' => $request->expiry_date,
            'delivery_time' => $request->delivery_time,
            'images' => json_encode($existingImages),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product->load('category')
        ]);
    }

    /**
     * Remove the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     * 
     * @OA\Delete(
     *     path="/api/admin/products/{id}",
     *     summary="Delete a product",
     *     description="Delete a product or mark it as inactive if it has existing orders",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted or deactivated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // Check if product is part of any orders
        if ($product->orderItems()->count() > 0) {
            // Instead of hard deleting, just mark as inactive
            $product->update(['is_active' => false]);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Product has been marked inactive because it is part of existing orders.'
            ]);
        }
        
        $product->delete();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }
}
