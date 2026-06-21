<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'user_name', 'password', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentSchool(): HasMany
    {
        return $this->hasMany(StudentSchool::class, 'school_id', 'id');
    }

    /**
     * Activity log entries for the user this school is linked to. Used by the
     * Filament SchoolResource so admins can see the audit trail directly.
     */
    public function userActivities(): HasMany
    {
        return $this->hasMany(\Spatie\Activitylog\Models\Activity::class, 'subject_id', 'user_id')
            ->where('subject_type', User::class)
            ->orderByDesc('created_at');
    }
}
