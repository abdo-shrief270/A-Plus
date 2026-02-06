<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'exam_id', 'exam_date', 'id_number'];

    protected function casts(): array
    {
        return [
            'exam_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }

    public function studentSchool()
    {
        return $this->hasOne(StudentSchool::class, 'student_id', 'id');
    }

    public function studentParent()
    {
        return $this->hasOne(StudentParent::class, 'student_id', 'id');
    }

    public function lessonProgress()
    {
        return $this->hasMany(StudentLessonProgress::class);
    }

    public function todayLessons()
    {
        return $this->lessonProgress()
            ->with('lesson.pages')
            ->whereDate('scheduled_date', today())
            ->ordered();
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class, 'current_league_id');
    }

    public function scores(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentScore::class);
    }

    public function wallet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
