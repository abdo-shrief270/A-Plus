<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A self-service quiz built by a student (scope + rules), with frozen
 * questions. Fully sandboxed: answers here never touch StudentAnswer,
 * ScoreService, or revision metrics.
 */
class QuizSession extends Model
{
    use HasFactory;

    public const MODE_TUTOR = 'tutor';
    public const MODE_EXAM = 'exam';

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ABANDONED = 'abandoned';

    /** Grace window after deadline_at to absorb network latency on last-second answers. */
    public const DEADLINE_GRACE_SECONDS = 5;

    protected $fillable = [
        'student_id',
        'mode',
        'source',
        'difficulty',
        'section_ids',
        'category_ids',
        'question_count',
        'time_limit_seconds',
        'status',
        'challenge_date',
        'is_simulation',
        'practice_exam_id',
        'challenge_id',
        'correct_count',
        'incorrect_count',
        'skipped_count',
        'score_percent',
        'started_at',
        'deadline_at',
        'completed_at',
    ];

    protected $casts = [
        'section_ids' => 'array',
        'category_ids' => 'array',
        'score_percent' => 'decimal:2',
        'challenge_date' => 'date',
        'is_simulation' => 'boolean',
        'started_at' => 'datetime',
        'deadline_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizSessionQuestion::class)->orderBy('order');
    }

    public function practiceExam(): BelongsTo
    {
        return $this->belongsTo(PracticeExam::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_EXPIRED], true);
    }

    public function isPastDeadline(): bool
    {
        return $this->deadline_at !== null
            && now()->gt($this->deadline_at->copy()->addSeconds(self::DEADLINE_GRACE_SECONDS));
    }

    public function isDailyChallenge(): bool
    {
        return $this->challenge_date !== null;
    }
}
