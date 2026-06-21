<?php

namespace App\Services;

use App\Exceptions\QuizConflictException;
use App\Models\Challenge;
use App\Models\QuizSession;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Challenge a friend: one student freezes a question set behind an invite
 * code; everyone who joins answers the identical set, and scores are compared.
 * Reuses the quiz engine for the actual play (exam mode, sandboxed).
 */
class ChallengeService
{
    public const SECONDS_PER_QUESTION = 60;
    public const EXPIRES_DAYS = 7;

    public function __construct(protected QuizService $quizService)
    {
    }

    /**
     * Create a challenge from a quiz-style config, freezing a random question
     * set, and start the creator's own session immediately.
     *
     * @return array{challenge: Challenge, session: QuizSession}
     */
    public function create(Student $student, array $config): array
    {
        if (!$student->exam_id) {
            throw ValidationException::withMessages(['exam' => ['يجب تحديد الاختبار أولاً.']]);
        }

        $config['category_ids'] = $this->quizService->expandScope(
            $student,
            $config['section_ids'] ?? [],
            $config['category_ids'] ?? []
        );

        if (empty($config['category_ids']) && ($config['source'] ?? 'random') !== 'bookmarked') {
            throw ValidationException::withMessages(['scope' => ['يجب اختيار قسم أو تصنيف واحد على الأقل.']]);
        }

        $count = (int) $config['question_count'];
        $questionIds = $this->quizService->buildPoolQuery($student, $config)
            ->inRandomOrder()->limit($count)->pluck('questions.id');

        if ($questionIds->count() < 2) {
            throw ValidationException::withMessages(['pool' => ['لا توجد أسئلة كافية لإنشاء تحدٍ.']]);
        }

        $timeLimit = !empty($config['time_limit_minutes'])
            ? ((int) $config['time_limit_minutes']) * 60
            : $questionIds->count() * self::SECONDS_PER_QUESTION;

        $challenge = Challenge::create([
            'creator_student_id' => $student->id,
            'invite_code' => $this->uniqueCode(),
            'question_ids' => $questionIds->values()->all(),
            'question_count' => $questionIds->count(),
            'time_limit_seconds' => $timeLimit,
            'status' => 'open',
            'expires_at' => now()->addDays(self::EXPIRES_DAYS),
        ]);

        $session = $this->startSessionFor($student, $challenge);

        return ['challenge' => $challenge, 'session' => $session];
    }

    /**
     * Join an existing challenge by code: returns the participant's session
     * over the frozen set. Re-joining returns the existing session.
     */
    public function join(Student $student, string $code): array
    {
        $challenge = Challenge::where('invite_code', $code)->first();
        if (!$challenge) {
            throw ValidationException::withMessages(['code' => ['رمز التحدي غير صحيح.']]);
        }
        if ($challenge->isExpired()) {
            throw ValidationException::withMessages(['code' => ['انتهت صلاحية هذا التحدي.']]);
        }

        $existing = QuizSession::where('challenge_id', $challenge->id)
            ->where('student_id', $student->id)->first();
        if ($existing) {
            return ['challenge' => $challenge, 'session' => $existing];
        }

        $session = $this->startSessionFor($student, $challenge);

        return ['challenge' => $challenge, 'session' => $session];
    }

    /** Leaderboard for a challenge: each participant's finished score. */
    public function results(Challenge $challenge): array
    {
        $rows = $challenge->sessions()
            ->with('student.user')
            ->get()
            ->map(fn (QuizSession $s) => [
                'student_id' => $s->student_id,
                'name' => $s->student?->user?->name,
                'status' => $s->status,
                'score_percent' => $s->status === QuizSession::STATUS_IN_PROGRESS
                    ? null
                    : (float) $s->score_percent,
                'correct_count' => $s->correct_count,
                'is_creator' => $s->student_id === $challenge->creator_student_id,
                'completed_at' => optional($s->completed_at)->toIso8601String(),
            ])
            // Finished first, by score desc; in-progress last.
            ->sortBy(fn ($r) => [$r['score_percent'] === null ? 1 : 0, -1 * ($r['score_percent'] ?? 0)])
            ->values();

        return [
            'invite_code' => $challenge->invite_code,
            'question_count' => $challenge->question_count,
            'status' => $challenge->status,
            'expires_at' => optional($challenge->expires_at)->toIso8601String(),
            'participants' => $rows->all(),
        ];
    }

    private function startSessionFor(Student $student, Challenge $challenge): QuizSession
    {
        try {
            return $this->quizService->createFrozenSession($student, [
                'mode' => QuizSession::MODE_EXAM,
                'source' => 'random',
                'time_limit_minutes' => (int) ceil(($challenge->time_limit_seconds ?? 0) / 60) ?: null,
                'challenge_id' => $challenge->id,
            ], $challenge->question_ids);
        } catch (QuizConflictException $e) {
            // Surface the existing active session so the client can resume.
            throw $e;
        }
    }

    private function uniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (Challenge::where('invite_code', $code)->exists());

        return $code;
    }
}
