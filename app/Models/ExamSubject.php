<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSubject extends Model
{
    protected $fillable = ['exam_id','name','description'];


    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            Question::class,             // related model
            'category_questions',        // pivot table name
            'section_category_id',       // foreign key on pivot for this model
            'question_id'                // foreign key on pivot for related model
        )->orderBy('questions.created_at', 'desc');
    }

    public function exam() :BelongsTo
    {
        return $this->belongsTo(Exam::class,'exam_id','id');
    }
}
