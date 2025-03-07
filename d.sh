#!/bin/bash

# Script to fix syntax error in ProductController.php
# Run this script on your Digital Ocean server

# Colors for pretty output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting ProductController syntax error fix...${NC}"

# Navigate to the Laravel project directory
cd /var/www/mmartplus || {
    echo -e "${RED}Failed to navigate to the Laravel project directory.${NC}"
    exit 1
}

# Backup the current ProductController file
echo -e "${YELLOW}Creating backup of the current ProductController...${NC}"
cp app/Http/Controllers/ProductController.php app/Http/Controllers/ProductController.php.backup.$(date +%Y%m%d%H%M%S)

# Fix the syntax issue by replacing the entire method implementation
echo -e "${YELLOW}Checking controller file structure...${NC}"

# Fixing the issue by directly editing the file with better syntax handling
# Create a temporary file
cat > /tmp/fixed_controller.php << 'EOL'
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Apply category filter
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply price range filter
        if ($request->has('min_price') && !empty($request->min_price)) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && !empty($request->max_price)) {
            $query->where('price', '<=', $request->max_price);
        }

        // Apply sorting
        $orderBy = $request->order_by ?? 'created_at';
        $orderDirection = $request->order_direction ?? 'desc';
        $query->orderBy($orderBy, $orderDirection);

        // Pagination
        $perPage = $request->per_page ?? 12;
        $products = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\ProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProductRequest $request)
    {
        // Generate slug from name
        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;

        // Ensure the slug is unique
        while (Product::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        // Create the product
        $product = new Product();
        $product->name = $request->name;
        $product->slug = $slug;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->category_id = $request->category_id;
        $product->sku = $request->sku;
        $product->is_active = $request->is_active ?? true;
        $product->save();

        // Process images if any
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $images[] = [
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'is_primary' => $index === 0
                ];
            }
            $product->images = json_encode($images);
            $product->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

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
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\ProductRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        // Update the product with the request data
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->stock = $request->stock;
        $product->category_id = $request->category_id;
        $product->sku = $request->sku;
        $product->is_active = $request->is_active ?? $product->is_active;

        // Update slug if name has changed
        if ($product->isDirty('name')) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $count = 1;

            // Ensure the slug is unique
            while (Product::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = "{$originalSlug}-{$count}";
                $count++;
            }

            $product->slug = $slug;
        }

        $product->save();

        // Process images if any
        if ($request->hasFile('images')) {
            // Remove old images from storage
            if (!empty($product->images)) {
                $oldImages = json_decode($product->images, true);
                foreach ($oldImages as $oldImage) {
                    if (isset($oldImage['path'])) {
                        Storage::disk('public')->delete($oldImage['path']);
                    }
                }
            }

            // Upload new images
            $images = [];
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $images[] = [
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'is_primary' => $index === 0
                ];
            }
            $product->images = json_encode($images);
            $product->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        // Remove product images from storage
        if (!empty($product->images)) {
            $images = json_decode($product->images, true);
            foreach ($images as $image) {
                if (isset($image['path'])) {
                    Storage::disk('public')->delete($image['path']);
                }
            }
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get the best selling products.
     *
     * @return \Illuminate\Http\Response
     */
    public function bestSellers()
    {
        // In a real application, you would determine best sellers based on order data
        // For now, let's return the top 8 products ordered by created_at
        $products = Product::where('is_active', true)
                        ->orderBy('created_at', 'desc')
                        ->take(8)
                        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Get the new arrivals (recently added products).
     *
     * @return \Illuminate\Http\Response
     */
    public function newArrivals()
    {
        $products = Product::where('is_active', true)
                        ->orderBy('created_at', 'desc')
                        ->take(8)
                        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Get related products (products in the same category).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function relatedProducts($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        $relatedProducts = Product::where('category_id', $product->category_id)
                              ->where('id', '!=', $id)
                              ->where('is_active', true)
                              ->take(4)
                              ->get();

        return response()->json([
            'status' => 'success',
            'data' => $relatedProducts
        ]);
    }
}
EOL

# Check if the temporary file was created successfully
if [ ! -f /tmp/fixed_controller.php ]; then
    echo -e "${RED}Failed to create temporary file for fixed controller.${NC}"
    exit 1
fi

# Replace the current controller with the fixed version
echo -e "${YELLOW}Replacing the controller with fixed version...${NC}"
cp /tmp/fixed_controller.php app/Http/Controllers/ProductController.php

# Update file permissions
echo -e "${YELLOW}Setting proper file permissions...${NC}"
chmod 644 app/Http/Controllers/ProductController.php
chown www-data:www-data app/Http/Controllers/ProductController.php

# Clean up
rm /tmp/fixed_controller.php

# Clear Laravel cache
echo -e "${YELLOW}Clearing Laravel cache...${NC}"
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan optimize

# Restart the web server
echo -e "${YELLOW}Restarting PHP-FPM...${NC}"
systemctl restart php8.1-fpm.service

echo -e "${GREEN}ProductController syntax fix completed successfully!${NC}"
echo -e "${YELLOW}Try the product slug endpoint again with Postman.${NC}"
echo -e "${YELLOW}URL: https://api.m-martplus.com/api/products/slug/{slug}${NC}"
echo -e "${YELLOW}If you encounter any issues, you can restore the backup from:${NC} app/Http/Controllers/ProductController.php.backup.*"

exit 0

