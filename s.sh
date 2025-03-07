#!/bin/bash

# Script to update the ProductController to support slug-based product retrieval
# Run this script on your Digital Ocean server

# Colors for pretty output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting M-Mart+ ProductController update script...${NC}"

# Navigate to the Laravel project directory
# Update this path if your Laravel project is in a different location
cd /var/www/mmartplus || {
    echo -e "${RED}Failed to navigate to the Laravel project directory. Please check the path.${NC}"
    exit 1
}

# Backup the current ProductController file
echo -e "${YELLOW}Creating backup of the current ProductController...${NC}"
cp app/Http/Controllers/ProductController.php app/Http/Controllers/ProductController.php.backup.$(date +%Y%m%d%H%M%S)

# Check if the ProductController already has the showBySlug method
if grep -q "showBySlug" app/Http/Controllers/ProductController.php; then
    echo -e "${YELLOW}The showBySlug method already exists in ProductController.${NC}"
else
    echo -e "${YELLOW}Adding showBySlug method to ProductController...${NC}"
    
    # Find the position where we want to insert the new method (just before the update method)
    insert_line=$(grep -n "update(" app/Http/Controllers/ProductController.php | head -1 | cut -d: -f1)
    
    if [ -z "$insert_line" ]; then
        echo -e "${RED}Could not find a suitable insertion point in ProductController.${NC}"
        exit 1
    fi
    
    # Create a temporary file with the new method
    cat > /tmp/showBySlug.php << 'EOL'

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
EOL
    
    # Insert the new method
    sed -i "${insert_line}r /tmp/showBySlug.php" app/Http/Controllers/ProductController.php
    
    # Remove temporary file
    rm /tmp/showBySlug.php
fi

# Ensure proper indexing for slug field in the database
echo -e "${YELLOW}Adding database index for product slug field...${NC}"

# Create a migration file for adding the index
php artisan make:migration add_slug_index_to_products_table --table=products

# Update the migration file
latest_migration=$(find database/migrations -name "*add_slug_index_to_products_table.php" | sort -r | head -1)

if [ -z "$latest_migration" ]; then
    echo -e "${RED}Could not find the migration file.${NC}"
    exit 1
fi

# Update the migration file content
cat > $latest_migration << 'EOL'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add index to slug field for faster lookups
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['slug']);
        });
    }
};
EOL

# Run the migration
echo -e "${YELLOW}Running database migration to add slug index...${NC}"
php artisan migrate

# Update file permissions
echo -e "${YELLOW}Setting proper file permissions...${NC}"
chmod 644 app/Http/Controllers/ProductController.php
chown www-data:www-data app/Http/Controllers/ProductController.php

# Clear Laravel cache
echo -e "${YELLOW}Clearing Laravel cache...${NC}"
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear
php artisan optimize

# Restart the web server
echo -e "${YELLOW}Restarting web server...${NC}"
service nginx restart
service php8.2-fpm restart # Update with your PHP version if different

echo -e "${GREEN}ProductController update completed successfully!${NC}"
echo -e "${YELLOW}The product slug endpoint should now be functional.${NC}"
echo -e "${YELLOW}If you encounter any issues, you can restore the backup from:${NC} app/Http/Controllers/ProductController.php.backup.*"

exit 0

