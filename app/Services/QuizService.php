<?php

namespace App\Services;

use App\Exceptions\QuizConflictException;
use App\Models\Answer;
use App\Models\Bookmark;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\QuizSession;
use App\Models\SectionCategory;
use App\Models\Student;
use App\Models\StudentAnswer;
use App\Models\StudentScore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Self-service student quizzes.
 *
 * Sandboxed by design: everything here reads the global answer/bookmark
 * tables to build pools but writes ONLY to quiz_sessions /
 * quiz_session_questions. Never touches StudentAnswer or ScoreService.
 */
class QuizService
{
    /** Daily challenge fixed configuration. */
    public const DAILY_QUESTION_COUNT = 10;
    public const DAILY_TIME_LIMIT_MINUTES = 10;
    /** League points awarded for completing the daily challenge. */
    public const DAILY_BONUS_BASE = 10;
    /** Extra league points when the daily score is >= 80%. */
    public const DAILY_BONUS_EXCELLENT = 10;

    /** Exam simulation: total questions and minutes-per-question pacing. */
    public const SIMULATION_QUESTION_COUNT = 30;
    public const SIMULATION_SECONDS_PER_QUESTION = 60;

    /**
     * Build the candidate-question query for a config.
     * $config keys: source, category_ids (already expanded+validated), difficulty.
     */
    public function buildPoolQuery(Student $student, array $config): Builder
    {
        $categoryIds = $config['category_ids'] ?? [];
        $query = Question::query();

        if (!empty($categoryIds)) {
            // Questions attach to a category directly (pivot) or via an article
            // that belongs to the category — pool must cover both paths.
            $query->where(function ($q) use ($categoryIds) {
                $q->whereHas('categories', fn ($c) => $c->whereIn('section_categories.id', $categoryIds))
                    ->orWhereHas('articles', fn ($a) => $a->whereIn('articles.section_category_id', $categoryIds));
            });
        }

        if (!empty($config['difficulty'])) {
            $query->where('difficulty', $config['difficulty']);
        }

        // Pools read global StudentAnswer/Bookmark state only — quiz answers
        // deliberately do not feed back into pools (sandbox).
        match ($config['source'] ?? 'random') {
            'unanswered' => $query->whereNotIn('id',
                StudentAnswer::where('user_id', $student->user_id)->select('question_id')),
            'wrong' => $query->whereIn('id',
                StudentAnswer::where('user_id', $student->user_id)->where('is_correct', false)->select('question_id')),
            'bookmarked' => $query->whereIn('id',
                Bookmark::where('student_id', $student->id)->select('question_id')),
            default => null,
        };

        return $query;
    }

    /**
     * Expand the student's raw scope selection (sections and/or categories)
     * into the effective category-id list, dropping anything that doesn't
     * belong to the student's exam.
     */
    public function expandScope(Student $student, array $sectionIds, array $categoryIds): array
    {
        $fromSections = empty($sectionIds)
            ? collect()
            : SectionCategory::whereIn('exam_section_id', $sectionIds)
                ->whereHas('section', fn ($s) => $s->where('exam_id', $student->exam_id))
                ->pluck('id');

        $direct = empty($categoryIds)
            ? collect()
            : SectionCategory::whereIn('id', $categoryIds)
                ->whereHas('section', fn ($s) => $s->where('exam_id', $student->exam_id))
                ->pluck('id');

        return $fromSections->merge($direct)->unique()->values()->all();
    }

    public function poolCount(Student $student, array $config): int
    {
        $config['category_ids'] = $this->expandScope(
            $student,
            $config['section_ids'] ?? [],
            $config['category_ids'] ?? []
        );

        if (empty($config['category_ids']) && ($config['source'] ?? 'random') !== 'bookmarked') {
            return 0;
        }

        return $this->buildPoolQuery($student, $config)->distinct()->count('questions.id');
    }

    public function createSession(Student $student, array $config): QuizSession
    {
        if (!$student->exam_id) {
            throw ValidationException::withMessages([
                'exam' => ['يجب تحديد الاختبار أولاً من الملف الشخصي.'],
            ]);
        }

        // A timed session whose deadline passed must not block new creates —
        // expire it here since no other endpoint may ever touch it again.
        QuizSession::where('student_id', $student->id)
            ->where('status', QuizSession::STATUS_IN_PROGRESS)
            ->get()
            ->each(fn (QuizSession $s) => $this->syncExpiry($s));

        $config['category_ids'] = $this->expandScope(
            $student,
            $config['section_ids'] ?? [],
            $config['category_ids'] ?? []
        );

        if (empty($config['category_ids']) && ($config['source'] ?? 'random') !== 'bookmarked') {
            throw ValidationException::withMessages([
                'scope' => ['يجب اختيار قسم أو تصنيف واحد على الأقل.'],
            ]);
        }

        $requested = (int) $config['question_count'];
        $questionIds = $this->buildPoolQuery($student, $config)
            ->inRandomOrder()
            ->limit($requested)
            ->pluck('questions.id');

        if ($questionIds->isEmpty()) {
            throw ValidationException::withMessages([
                'pool' => ['لا توجد أسئلة مطابقة للاختيارات الحالية.'],
            ]);
        }

        return $this->persistSession($student, $config, $questionIds);
    }

    /**
     * Public wrapper to start an exam-mode session over an explicit, frozen
     * set of question ids (used by Challenge a Friend, where every participant
     * answers the identical set). Expires stale timed sessions first.
     */
    public function createFrozenSession(Student $student, array $config, $questionIds): QuizSession
    {
        QuizSession::where('student_id', $student->id)
            ->where('status', QuizSession::STATUS_IN_PROGRESS)
            ->get()
            ->each(fn (QuizSession $s) => $this->syncExpiry($s));

        return $this->persistSession($student, $config, collect($questionIds));
    }

    /**
     * Shared, concurrency-safe session writer used by every entry point
     * (standard quiz, daily challenge, exam simulation). Locks the student's
     * in-progress rows so parallel creates serialize, then freezes the
     * given question ids in order.
     */
    private function persistSession(Student $student, array $config, $questionIds): QuizSession
    {
        $timeLimitSeconds = !empty($config['time_limit_minutes'])
            ? ((int) $config['time_limit_minutes']) * 60
            : null;

        return DB::transaction(function () use ($student, $config, $questionIds, $timeLimitSeconds) {
            $active = QuizSession::where('student_id', $student->id)
                ->where('status', QuizSession::STATUS_IN_PROGRESS)
                ->lockForUpdate()
                ->first();
            if ($active) {
                throw new QuizConflictException('لديك اختبار قيد التنفيذ بالفعل.', [
                    'active_session_id' => $active->id,
                ]);
            }

            $now = now();
            $session = QuizSession::create([
                'student_id' => $student->id,
                'mode' => $config['mode'],
                'source' => $config['source'] ?? 'random',
                'difficulty' => $config['difficulty'] ?? null,
                'section_ids' => $config['section_ids'] ?? [],
                'category_ids' => $config['category_ids'] ?? [],
                'question_count' => $questionIds->count(),
                'time_limit_seconds' => $timeLimitSeconds,
                'status' => QuizSession::STATUS_IN_PROGRESS,
                // Daily challenge sets this at insert time so the DB unique
                // (student_id, challenge_date) atomically rejects same-day twins.
                'challenge_date' => $config['challenge_date'] ?? null,
                'is_simulation' => $config['is_simulation'] ?? false,
                'practice_exam_id' => $config['practice_exam_id'] ?? null,
                'challenge_id' => $config['challenge_id'] ?? null,
                'started_at' => $now,
                'deadline_at' => $timeLimitSeconds ? $now->copy()->addSeconds($timeLimitSeconds) : null,
            ]);

            $rows = collect($questionIds)->values()->map(fn ($qid, $i) => [
                'quiz_session_id' => $session->id,
                'question_id' => $qid,
                'order' => $i + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            DB::table('quiz_session_questions')->insert($rows);

            return $session;
        });
    }

    /**
     * Lazily finalize a session whose deadline passed. Idempotent; called at
     * the top of every session-touching endpoint, so no scheduler is needed.
     */
    public function syncExpiry(QuizSession $session): QuizSession
    {
        if ($session->isInProgress() && $session->isPastDeadline()) {
            $this->finalize($session, QuizSession::STATUS_EXPIRED, $session->deadline_at);
        }

        return $session;
    }

    /**
     * Record an answer on the quiz row only.
     * Returns the feedback payload (mode-dependent).
     */
    public function answerQuestion(QuizSession $session, int $questionId, int $answerId): array
    {
        $this->syncExpiry($session);

        if (!$session->isInProgress()) {
            throw new QuizConflictException(
                $session->status === QuizSession::STATUS_EXPIRED
                    ? 'انتهى وقت الاختبار.'
                    : 'هذا الاختبار منتهي.',
                ['session_status' => $session->status]
            );
        }

        $answer = Answer::where('id', $answerId)->where('question_id', $questionId)->first();
        if (!$answer) {
            throw ValidationException::withMessages([
                'answer_id' => ['الإجابة المحددة لا تنتمي لهذا السؤال.'],
            ]);
        }

        // Row-lock so two parallel submissions serialize — without this, both
        // could pass the tutor "already answered" check (TOCTOU).
        DB::transaction(function () use ($session, $questionId, $answer) {
            $row = $session->questions()
                ->where('question_id', $questionId)
                ->lockForUpdate()
                ->first();
            if (!$row) {
                throw ValidationException::withMessages([
                    'question_id' => ['هذا السؤال ليس ضمن الاختبار.'],
                ]);
            }

            if ($session->mode === QuizSession::MODE_TUTOR && $row->isAnswered()) {
                throw ValidationException::withMessages([
                    'question_id' => ['تمت الإجابة على هذا السؤال بالفعل.'],
                ]);
            }

            $row->update([
                'answer_id' => $answer->id,
                'is_correct' => (bool) $answer->is_correct,
                'answered_at' => now(),
            ]);
        });

        $answeredCount = $session->questions()->whereNotNull('answered_at')->count();

        if ($session->mode === QuizSession::MODE_TUTOR) {
            $question = Question::with('answers')->find($questionId);

            return [
                'is_correct' => (bool) $answer->is_correct,
                'correct_answer_id' => $question->answers->firstWhere('is_correct', true)?->id,
                'explanation' => [
                    'text' => $question->explanation_text,
                    'video_url' => $question->explanation_video_url,
                ],
                'answered_count' => $answeredCount,
            ];
        }

        return [
            'acknowledged' => true,
            'answered_count' => $answeredCount,
        ];
    }

    /** Finalize as completed. Idempotent: returns finalized sessions as-is. */
    public function completeSession(QuizSession $session): QuizSession
    {
        $this->syncExpiry($session);

        if (!$session->isInProgress()) {
            return $session;
        }

        $this->finalize($session, QuizSession::STATUS_COMPLETED, now());

        if ($session->isDailyChallenge()) {
            $this->awardDailyBonus($session);
        }

        return $session;
    }

    /**
     * Today's daily-challenge session for the student, if any (any status).
     */
    public function todaysChallenge(Student $student): ?QuizSession
    {
        return QuizSession::where('student_id', $student->id)
            ->whereDate('challenge_date', now()->toDateString())
            ->first();
    }

    /**
     * Start (or resume) today's daily challenge: a fixed-config quiz over the
     * student's whole exam. Returns the existing session if already started.
     */
    public function startDailyChallenge(Student $student): QuizSession
    {
        $existing = $this->todaysChallenge($student);
        if ($existing) {
            $this->syncExpiry($existing);

            return $existing;
        }

        $allCategoryIds = SectionCategory::whereHas(
            'section',
            fn ($s) => $s->where('exam_id', $student->exam_id)
        )->pluck('id')->all();

        try {
            return $this->createSession($student, [
                'mode' => QuizSession::MODE_EXAM,
                'source' => 'random',
                'category_ids' => $allCategoryIds,
                'question_count' => self::DAILY_QUESTION_COUNT,
                'time_limit_minutes' => self::DAILY_TIME_LIMIT_MINUTES,
                'challenge_date' => now()->toDateString(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Concurrent starts can lose two ways: the (student_id,
            // challenge_date) unique rejects a twin insert, or InnoDB picks
            // this transaction as the deadlock victim. Either way a winner
            // exists (or is committing) — hand back its session.
            $isDuplicate = $e instanceof \Illuminate\Database\UniqueConstraintViolationException;
            $isDeadlock = ($e->errorInfo[1] ?? null) === 1213;
            if (!$isDuplicate && !$isDeadlock) {
                throw $e;
            }

            // Tiny grace for the winner's commit to land, then re-read.
            usleep(150_000);
            $winner = $this->todaysChallenge($student);
            if ($winner) {
                return $winner;
            }

            // Deadlock with no committed winner: one clean retry.
            return $this->startDailyChallenge($student);
        }
    }

    /**
     * Build an exam simulation: a single exam-mode session that draws questions
     * from EVERY section of the student's exam, distributed proportionally to
     * each section's available pool (largest-remainder method, ≥1 per non-empty
     * section). This section coverage is what distinguishes a simulation from a
     * plain random quiz. Time scales at one minute per question.
     */
    public function startSimulation(Student $student): QuizSession
    {
        if (!$student->exam_id) {
            throw ValidationException::withMessages([
                'exam' => ['يجب تحديد الاختبار أولاً من الملف الشخصي.'],
            ]);
        }

        $sections = ExamSection::where('exam_id', $student->exam_id)
            ->with('categories:id,exam_section_id')
            ->get();

        // Available pool per section (questions linked directly or via article).
        $sectionPools = [];
        $allCategoryIds = [];
        foreach ($sections as $section) {
            $catIds = $section->categories->pluck('id')->all();
            if (empty($catIds)) {
                continue;
            }
            $allCategoryIds = array_merge($allCategoryIds, $catIds);
            $count = $this->buildPoolQuery($student, [
                'source' => 'random',
                'category_ids' => $catIds,
            ])->distinct()->count('questions.id');
            if ($count > 0) {
                $sectionPools[$section->id] = ['category_ids' => $catIds, 'available' => $count];
            }
        }

        if (empty($sectionPools)) {
            throw ValidationException::withMessages([
                'pool' => ['لا توجد أسئلة كافية لإنشاء محاكاة لهذا الاختبار.'],
            ]);
        }

        $target = self::SIMULATION_QUESTION_COUNT;
        $allocation = $this->allocateProportionally($sectionPools, $target);

        // Pull each section's allotment, then interleave by shuffling the union.
        $questionIds = collect();
        foreach ($allocation as $sectionId => $take) {
            if ($take < 1) {
                continue;
            }
            $ids = $this->buildPoolQuery($student, [
                'source' => 'random',
                'category_ids' => $sectionPools[$sectionId]['category_ids'],
            ])->inRandomOrder()->limit($take)->pluck('questions.id');
            $questionIds = $questionIds->merge($ids);
        }

        $questionIds = $questionIds->unique()->shuffle()->values();
        if ($questionIds->isEmpty()) {
            throw ValidationException::withMessages([
                'pool' => ['لا توجد أسئلة كافية لإنشاء محاكاة لهذا الاختبار.'],
            ]);
        }

        $minutes = (int) ceil($questionIds->count() * self::SIMULATION_SECONDS_PER_QUESTION / 60);

        // Expire any stale timed session first (mirrors createSession).
        QuizSession::where('student_id', $student->id)
            ->where('status', QuizSession::STATUS_IN_PROGRESS)
            ->get()
            ->each(fn (QuizSession $s) => $this->syncExpiry($s));

        return $this->persistSession($student, [
            'mode' => QuizSession::MODE_EXAM,
            'source' => 'random',
            'section_ids' => array_keys($sectionPools),
            'category_ids' => array_values(array_unique($allCategoryIds)),
            'time_limit_minutes' => $minutes,
            'is_simulation' => true,
        ], $questionIds);
    }

    /**
     * Run a practice-exam model (نموذج) as a timed, exam-mode session: every
     * question in the model is frozen in order, time scales at one minute per
     * question. Models are global practice papers, so there's no exam gating —
     * any student may train on any active model. Sandboxed like every quiz
     * (answers never touch StudentAnswer / points / wallet).
     */
    public function startFromModel(Student $student, \App\Models\PracticeExam $model): QuizSession
    {
        if (!$model->is_active) {
            throw ValidationException::withMessages([
                'model' => ['هذا النموذج غير متاح حالياً.'],
            ]);
        }

        $questionIds = $model->questions()->orderBy('id')->pluck('id');
        if ($questionIds->isEmpty()) {
            throw ValidationException::withMessages([
                'model' => ['لا توجد أسئلة في هذا النموذج.'],
            ]);
        }

        $minutes = (int) ceil($questionIds->count() * self::SIMULATION_SECONDS_PER_QUESTION / 60);

        // Expire any stale timed session first (mirrors createSession).
        QuizSession::where('student_id', $student->id)
            ->where('status', QuizSession::STATUS_IN_PROGRESS)
            ->get()
            ->each(fn (QuizSession $s) => $this->syncExpiry($s));

        return $this->persistSession($student, [
            'mode' => QuizSession::MODE_EXAM,
            'source' => 'random',
            'time_limit_minutes' => $minutes,
            'practice_exam_id' => $model->id,
        ], $questionIds);
    }

    /**
     * Distribute $target across sections proportionally to available pool,
     * giving each non-empty section at least 1, capped at its availability,
     * with leftovers assigned by largest fractional remainder.
     *
     * @param  array<int, array{available: int}>  $sectionPools
     * @return array<int, int>  sectionId => questionCount
     */
    private function allocateProportionally(array $sectionPools, int $target): array
    {
        $totalAvailable = array_sum(array_column($sectionPools, 'available'));
        $target = min($target, $totalAvailable);

        $alloc = [];
        $remainders = [];
        $assigned = 0;
        foreach ($sectionPools as $id => $pool) {
            $exact = $target * ($pool['available'] / $totalAvailable);
            $base = min($pool['available'], max(1, (int) floor($exact)));
            $alloc[$id] = $base;
            $remainders[$id] = $exact - floor($exact);
            $assigned += $base;
        }

        // Trim if the ≥1 floor over-assigned; top up by largest remainder.
        arsort($remainders);
        while ($assigned > $target) {
            foreach (array_keys($remainders) as $id) {
                if ($assigned <= $target) {
                    break;
                }
                if ($alloc[$id] > 1) {
                    $alloc[$id]--;
                    $assigned--;
                }
            }
        }
        while ($assigned < $target) {
            $toppedUp = false;
            foreach (array_keys($remainders) as $id) {
                if ($assigned >= $target) {
                    break;
                }
                if ($alloc[$id] < $sectionPools[$id]['available']) {
                    $alloc[$id]++;
                    $assigned++;
                    $toppedUp = true;
                }
            }
            if (!$toppedUp) {
                break; // every section maxed out
            }
        }

        return $alloc;
    }

    /**
     * Consecutive-day streak of COMPLETED daily challenges, counting back
     * from today (or yesterday, so an unplayed "today" doesn't break it).
     */
    public function dailyStreak(Student $student): int
    {
        $dates = QuizSession::where('student_id', $student->id)
            ->whereNotNull('challenge_date')
            ->where('status', QuizSession::STATUS_COMPLETED)
            ->orderByDesc('challenge_date')
            ->pluck('challenge_date')
            ->map(fn ($d) => $d->toDateString())
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $cursor = now()->startOfDay();
        if ($dates->first() !== $cursor->toDateString()) {
            $cursor = $cursor->subDay();
            if ($dates->first() !== $cursor->toDateString()) {
                return 0;
            }
        }

        $streak = 0;
        foreach ($dates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /**
     * League-point bonus for finishing the daily challenge. This is the ONE
     * deliberate exception to the quiz sandbox: per-answer scoring stays
     * isolated, but completing the daily challenge feeds the leaderboard.
     * Guarded against double-award; returns the points granted (0 if none).
     */
    public function awardDailyBonus(QuizSession $session): int
    {
        $alreadyAwarded = StudentScore::where('reason', 'daily_challenge')
            ->where('reference_type', QuizSession::class)
            ->where('reference_id', $session->id)
            ->exists();
        if ($alreadyAwarded) {
            return 0;
        }

        $bonus = self::DAILY_BONUS_BASE;
        if ((float) $session->score_percent >= 80) {
            $bonus += self::DAILY_BONUS_EXCELLENT;
        }

        app(ScoreService::class)->addScore($session->student, $bonus, 'daily_challenge', $session);
        $session->setAttribute('daily_bonus_awarded', $bonus);

        return $bonus;
    }

    public function abandonSession(QuizSession $session): QuizSession
    {
        $this->syncExpiry($session);

        if (!$session->isInProgress()) {
            return $session;
        }

        // One challenge per day is enforced by a unique index, so an abandoned
        // daily session would lock the student out of today's challenge entirely.
        if ($session->isDailyChallenge()) {
            throw ValidationException::withMessages([
                'session' => ['لا يمكن إلغاء التحدي اليومي — أكمله أو انتظر انتهاء وقته.'],
            ]);
        }

        $session->update([
            'status' => QuizSession::STATUS_ABANDONED,
            'completed_at' => now(),
        ]);

        return $session;
    }

    protected function finalize(QuizSession $session, string $status, $completedAt): QuizSession
    {
        return DB::transaction(function () use ($session, $status, $completedAt) {
            // Re-read under lock: a parallel finalize (complete vs lazy expiry)
            // must not double-run; first committer wins, the loser sees the
            // finalized status and returns it untouched.
            $fresh = QuizSession::lockForUpdate()->find($session->id);
            if (!$fresh || !$fresh->isInProgress()) {
                return $fresh ?? $session;
            }

            $rows = $fresh->questions()->get();
            $correct = $rows->where('is_correct', true)->count();
            $incorrect = $rows->where('is_correct', false)->whereNotNull('answer_id')->count();
            $skipped = $rows->whereNull('answer_id')->count();
            $total = max(1, $rows->count());

            $fresh->update([
                'status' => $status,
                'correct_count' => $correct,
                'incorrect_count' => $incorrect,
                'skipped_count' => $skipped,
                // Admins may delete questions mid-session (rows cascade away);
                // keep the stored count consistent with what was actually graded.
                'question_count' => $rows->count() > 0 ? $rows->count() : $fresh->question_count,
                'score_percent' => round($correct / $total * 100, 2),
                'completed_at' => $completedAt,
            ]);

            // Keep the caller's instance in sync with what was committed.
            $session->setRawAttributes($fresh->getAttributes(), true);

            return $session;
        });
    }
}
