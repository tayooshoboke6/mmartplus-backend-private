# Product Sections Database Implementation Plan

## Database Migration

We need to create a migration for the `product_sections` table. Here's the planned structure:

```php
Schema::create('product_sections', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('type')->default('featured'); // featured, new, sale, etc.
    $table->string('background_color')->default('#f7f7f7');
    $table->string('text_color')->default('#333333');
    $table->json('product_ids'); // Store as JSON array
    $table->integer('display_order')->default(0);
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

## Model Implementation

The ProductSection model should look like this:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'background_color',
        'text_color',
        'product_ids',
        'display_order',
        'active',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'active' => 'boolean',
        'display_order' => 'integer',
    ];
    
    /**
     * Get the products that belong to this section.
     */
    public function products()
    {
        return Product::whereIn('id', $this->product_ids);
    }
}
```

## Controller Implementation

ProductSectionController should have these methods:

1. `index()` - List all product sections
2. `store()` - Create a new product section
3. `show()` - Get a specific product section
4. `update()` - Update a product section
5. `destroy()` - Delete a product section
6. `toggleStatus()` - Toggle active status
7. `reorder()` - Update display order of multiple sections

## API Routes

Add these routes to the API routes file:

```php
Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum', 'admin']], function () {
    Route::apiResource('product-sections', ProductSectionController::class);
    Route::post('product-sections/reorder', [ProductSectionController::class, 'reorder']);
    Route::patch('product-sections/{section}/toggle', [ProductSectionController::class, 'toggleStatus']);
});
```

## Implementation Steps

1. Create the migration file:
   ```
   php artisan make:migration create_product_sections_table
   ```

2. Create the model:
   ```
   php artisan make:model ProductSection
   ```

3. Create the controller:
   ```
   php artisan make:controller ProductSectionController --resource
   ```

4. Implement validation rules to ensure:
   - Duplicate titles are prevented
   - All required fields are present
   - JSON fields are properly formatted
   - Display order is always a positive integer

5. Run the migration:
   ```
   php artisan migrate
   ```

6. Test API endpoints with Postman or curl before connecting to frontend
