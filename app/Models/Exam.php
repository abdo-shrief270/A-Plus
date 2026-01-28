<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class, 'exam_id', 'id');
    }

    public function sectionsData(): HasMany
    {
        return $this->hasMany(ExamSection::class, 'exam_id', 'id')->select('id', 'name', 'exam_id')->with('categoriesData');
    }

    public function subjects(): HasMany
    {
        return $this->hasMany(ExamSubject::class, 'exam_id', 'id');
    }

    public function subjectsData(): HasMany
    {
        return $this->hasMany(ExamSubject::class, 'exam_id', 'id')->with('questions');
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
}
