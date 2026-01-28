<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Question extends Model
{
    use HasFactory;

    protected $fillable = ['text', 'image_path', 'explanation_text', 'explanation_text_image_path', 'explanation_video_url', 'difficulty'];
    protected $hidden = ['pivot'];
    protected $casts = [
        'difficulty' => 'string',
    ];

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'question_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo(QuestionType::class, 'question_type_id', 'id');
    }

    public function subjects()
    {
        return $this->belongsToMany(ExamSubject::class, 'subject_questions', 'question_id', 'exam_subject_id');
    }

    public function categories()
    {
        return $this->belongsToMany(SectionCategory::class, 'category_questions', 'question_id', 'section_category_id');
    }

    public function getImagePathAttribute($value): ?string
    {
        return $value ? url(Storage::url($value)) : null;
    }

    public function getExplanationTextImagePathAttribute($value): ?string
    {
        return $value ? url(Storage::url($value)) : null;
    }
}
