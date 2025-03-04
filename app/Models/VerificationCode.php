<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class VerificationCode extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'phone_number',
        'code',
        'expires_at',
        'is_used',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];
    
    /**
     * Get the user that owns the verification code.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Determine if the verification code has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
    
    /**
     * Determine if the verification code is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->is_used;
    }
    
    /**
     * Mark the verification code as used.
     *
     * @return bool
     */
    public function markAsUsed(): bool
    {
        $this->is_used = true;
        return $this->save();
    }
}
