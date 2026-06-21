<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'icon',
        'is_published',
        'is_locked',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_locked' => 'boolean',
    ];

    public function scopePublished($q)
    {
        return $q->where('is_published', true);
    }
}
