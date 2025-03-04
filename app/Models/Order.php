<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'status',
        'payment_status',
        'payment_method',
        'total',
        'tax',
        'shipping',
        'discount',
        'voucher_code',
        'notes',
        'shipping_address',
        'billing_address',
        'tracking_number',
        'email',
        'phone',
        'in_store_pickup'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total' => 'float',
        'tax' => 'float',
        'shipping' => 'float',
        'discount' => 'float',
        'shipping_address' => 'json',
        'billing_address' => 'json',
        'in_store_pickup' => 'boolean',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the items for the order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Determine if the order is cancelled.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Determine if the order is completed.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Determine if the order is paid.
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Calculate the total from order items.
     *
     * @return float
     */
    public function calculateTotal()
    {
        return $this->items->sum('total');
    }

    /**
     * Scope a query to only include orders with specified status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the voucher usages for this order
     */
    public function voucherUsage()
    {
        return $this->hasOne(VoucherUsage::class);
    }
}
