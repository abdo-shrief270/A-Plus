<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LatexFormat extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'category',
        'icon',
        'inputs',
        'template',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'inputs' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
