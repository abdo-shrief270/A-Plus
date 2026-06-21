<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Student extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['user_id', 'exam_id', 'exam_date', 'id_number'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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

    /**
     * True when the student has an active subscription plan (any duration —
     * weekly/monthly/yearly). Such students get unlimited content access and
     * are never charged from their wallet. Mirrors the `has_unlimited_points`
     * flag computed in StudentService so both stay consistent.
     */
    public function hasUnlimitedAccess(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->whereHas('plan', fn ($pq) => $pq->whereIn('type', ['subscription', 'trial']))
            ->exists();
    }

    public function bookmarks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function bookmarkedQuestions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'bookmarks', 'student_id', 'question_id')
            ->withTimestamps();
    }
}
