<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionCategory extends Model
{
    use HasFactory;

    protected $fillable = ['exam_section_id', 'name', 'description'];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            Question::class,             // related model
            'category_questions',        // pivot table name
            'section_category_id',       // foreign key on pivot for this model
            'question_id'                // foreign key on pivot for related model
        );
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ExamSection::class, 'exam_section_id', 'id');
    }
}
