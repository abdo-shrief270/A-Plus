<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'slug',
        'image_path',
        'price',
        'active',
        'start_date',
        'end_date',
        'level',
        'total_hours',
        'lectures_count',
        'rating',
    ];

    protected $casts = [
        'active' => 'boolean',
        'price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'rating' => 'decimal:2',
    ];

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'course_exam');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
