<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class League extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon_path',
        'min_score',
        'color',
        'order',
    ];

    public function getIconPathAttribute($value): ?string
    {
        return $value ? url(Storage::url($value)) : null;
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'current_league_id');
    }
}
