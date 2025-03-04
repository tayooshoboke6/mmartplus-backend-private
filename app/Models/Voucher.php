<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_spend',
        'expires_at',
        'is_active',
        'max_usage_per_user',
        'total_usage',
        'max_total_usage',
        'description',
        'qualification_type', // 'manual', 'automatic', 'targeted'
        'criteria_json',      // JSON field for storing qualification criteria
    ];

    protected $casts = [
        'value' => 'float',
        'min_spend' => 'float',
        'is_active' => 'boolean',
        'max_usage_per_user' => 'integer',
        'total_usage' => 'integer',
        'max_total_usage' => 'integer',
        'expires_at' => 'datetime',
        'criteria_json' => 'array',
    ];

    /**
     * Get the voucher usages for this voucher
     */
    public function usages(): HasMany
    {
        return $this->hasMany(VoucherUsage::class);
    }

    /**
     * Get the categories this voucher is applicable to
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'voucher_categories');
    }

    /**
     * Get the products this voucher is applicable to
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'voucher_products');
    }

    /**
     * Get the users this voucher is assigned to
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_vouchers')
            ->withPivot('is_redeemed', 'redeemed_at')
            ->withTimestamps();
    }

    /**
     * Check if voucher is valid
     */
    public function isValid(): bool
    {
        return $this->is_active && 
               (!$this->expires_at || $this->expires_at->isFuture()) &&
               ($this->max_total_usage === null || $this->total_usage < $this->max_total_usage);
    }

    /**
     * Check if voucher is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if voucher is fully used up
     */
    public function isFullyRedeemed(): bool
    {
        return $this->max_total_usage !== null && $this->total_usage >= $this->max_total_usage;
    }
}
