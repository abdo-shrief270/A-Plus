<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Question extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'text',
        'image_path',
        'explanation_text',
        'explanation_text_image_path',
        'explanation_video_url',
        'difficulty',
        'is_new',
        'practice_exam_id'
    ];
    protected $hidden = ['pivot'];
    protected $casts = [
        'difficulty' => 'string',
        'is_new' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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

    public function practiceExam()
    {
        return $this->belongsTo(PracticeExam::class);
    }

    public function getImagePathAttribute($value): ?string
    {
        return $value ? url(Storage::url($value)) : null;
    }

    public function getExplanationTextImagePathAttribute($value): ?string
    {
        return $value ? url(Storage::url($value)) : null;
    }

    public function scopeTrending($query)
    {
        return $query->where('is_new', true);
    }

    public function articles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }
}
