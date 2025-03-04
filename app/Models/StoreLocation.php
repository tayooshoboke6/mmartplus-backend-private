<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreLocation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'zip_code',
        'phone',
        'email',
        'description',
        'opening_hours',
        'pickup_instructions',
        'pickup_available',
        'is_active',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'opening_hours' => 'array',
        'pickup_available' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];
    
    /**
     * Get the full address as a single string.
     *
     * @return string
     */
    public function getFullAddressAttribute()
    {
        return "{$this->address}, {$this->city}, {$this->state} {$this->zip_code}";
    }
}
