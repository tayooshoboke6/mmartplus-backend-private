<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'short_description',
        'price',
        'stock',
        'sku',
        'is_active',
        'is_featured',
        'category_id',
        'images',
        'expiry_date',
        'slug',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'images' => 'array',
        'expiry_date' => 'date',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Determine if the product is in stock.
     *
     * @return bool
     */
    public function inStock()
    {
        return $this->stock > 0;
    }
    
    /**
     * Restore the product stock.
     *
     * @param int $quantity
     * @return void
     */
    public function restoreStock($quantity)
    {
        $this->stock += $quantity;
        $this->save();
    }

    /**
     * Reduce the product stock.
     *
     * @param int $quantity
     * @return void
     * @throws \Exception
     */
    public function reduceStock($quantity)
    {
        if ($this->stock < $quantity) {
            throw new \Exception("Not enough stock available");
        }
        
        $this->stock -= $quantity;
        $this->save();
    }

    /**
     * Scope a query to only include active products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
