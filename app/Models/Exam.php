<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Exam extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['name', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** Subjects (section categories) across all of the exam's sections. */
    public function categories(): HasManyThrough
    {
        return $this->hasManyThrough(
            SectionCategory::class,
            ExamSection::class,
            'exam_id',          // FK on exam_sections
            'exam_section_id',  // FK on section_categories
            'id',
            'id'
        );
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class, 'exam_id', 'id');
    }

    public function sectionsData(): HasMany
    {
        return $this->hasMany(ExamSection::class, 'exam_id', 'id')->select('id', 'name', 'exam_id')->with('categoriesData');
    }

    public function sectionsCategories()
    {
        return $this->hasMany(ExamSection::class, 'exam_id', 'id')->select('id', 'name', 'exam_id')->with('categories');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->ordered();
    }

    public function activeLessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->active()->ordered();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
