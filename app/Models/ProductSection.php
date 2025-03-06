<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSection extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'product_ids' => 'array',
        'active' => 'boolean',
        'display_order' => 'integer',
    ];
    
    /**
     * Get the products that belong to this section.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsAttribute()
    {
        return Product::whereIn('id', $this->product_ids)->get();
    }
}
