<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'device_id',
        'name',
        'platform',
        'ip_address',
        'user_agent',
        'last_login_at',
        'is_trusted',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'is_trusted' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter only trusted devices.
     */
    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    /**
     * Scope to filter only approved devices.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Update last login timestamp.
     */
    public function updateLastLogin(): bool
    {
        $this->last_login_at = now();
        return $this->save();
    }
}
