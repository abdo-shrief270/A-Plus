<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSection extends Model
{
    use HasFactory;

    protected $fillable = ['exam_id', 'name'];

    public function categories(): HasMany
    {
        return $this->hasMany(SectionCategory::class, 'exam_section_id', 'id');
    }

    public function categoriesData(): HasMany
    {
        return $this->hasMany(SectionCategory::class, 'exam_section_id', 'id')->select('id', 'name', 'exam_section_id', 'description')->with('questions');
    }
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }
}
