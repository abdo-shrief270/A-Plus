<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Article extends Model
{
    use HasFactory;

    protected $fillable = ['section_category_id', 'title', 'content', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(SectionCategory::class, 'section_category_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }
}
