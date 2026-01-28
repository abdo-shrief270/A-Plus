<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonPage extends Model
{
    protected $fillable = [
        'lesson_id',
        'page_number',
        'type',
        'title',
        'content',
        'is_required',
    ];

    protected $casts = [
        'content' => 'array',
        'is_required' => 'boolean',
        'page_number' => 'integer',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('page_number');
    }

    /**
     * Get content structure based on page type
     * 
     * For 'text': ['body' => 'text content']
     * For 'image': ['image_url' => '...', 'caption' => '...']
     * For 'question': ['question_id' => 1, 'instructions' => '...']
     * For 'mixed': ['sections' => [['type' => 'text', 'content' => '...'], ...]]
     */
    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }
}
