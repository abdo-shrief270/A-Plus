<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Exam;
use App\Models\StudentAnswer;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class RevisionMetricsService
{
    /**
     * Build the revision metrics bundle for a single student.
     *
     * Returns:
     *   - exam:       { id, name }
     *   - totals:     aggregate counts (total questions in the exam, attempts,
     *                 unique answered, correct, accuracy %, points)
     *   - sections:   per-section list, each with per-category breakdown
     *                 (totals + answered + correct + accuracy)
     *   - recent:     last 5 attempts (question id, correct, when)
     */
    public function bundle(Student $student): array
    {
        $exam = $student->exam_id ? Exam::with(['sections.categories'])->find($student->exam_id) : null;

        if (!$exam) {
            return [
                'exam' => null,
                'totals' => $this->emptyTotals(),
                'sections' => [],
                'recent' => [],
                'bookmarks_count' => Bookmark::where('student_id', $student->id)->count(),
            ];
        }

        $categoryIds = $exam->sections->flatMap(fn ($s) => $s->categories->pluck('id'))->unique()->values();

        // Total questions per category (via category_questions pivot).
        $totalsPerCategory = DB::table('category_questions')
            ->select('section_category_id', DB::raw('COUNT(DISTINCT question_id) as total'))
            ->whereIn('section_category_id', $categoryIds)
            ->groupBy('section_category_id')
            ->pluck('total', 'section_category_id');

        // Per-category student attempts: distinct answered + correct.
        $studentAggPerCategory = DB::table('student_answers as sa')
            ->join('category_questions as cq', 'cq.question_id', '=', 'sa.question_id')
            ->select(
                'cq.section_category_id',
                DB::raw('COUNT(DISTINCT sa.question_id) as answered'),
                DB::raw('COUNT(DISTINCT CASE WHEN sa.is_correct = 1 THEN sa.question_id END) as correct')
            )
            ->where('sa.student_id', $student->id)
            ->whereIn('cq.section_category_id', $categoryIds)
            ->groupBy('cq.section_category_id')
            ->get()
            ->keyBy('section_category_id');

        $sections = $exam->sections->map(function ($section) use ($totalsPerCategory, $studentAggPerCategory) {
            $categories = $section->categories->map(function ($cat) use ($totalsPerCategory, $studentAggPerCategory) {
                $total = (int) ($totalsPerCategory[$cat->id] ?? 0);
                $row = $studentAggPerCategory[$cat->id] ?? null;
                $answered = (int) ($row->answered ?? 0);
                $correct = (int) ($row->correct ?? 0);
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'total' => $total,
                    'answered' => $answered,
                    'correct' => $correct,
                    'accuracy' => $answered > 0 ? round(($correct / $answered) * 100, 1) : 0,
                    'progress' => $total > 0 ? round(($answered / $total) * 100, 1) : 0,
                ];
            });

            $totalQuestions = $categories->sum('total');
            $totalAnswered = $categories->sum('answered');
            $totalCorrect = $categories->sum('correct');

            return [
                'id' => $section->id,
                'name' => $section->name,
                'total' => $totalQuestions,
                'answered' => $totalAnswered,
                'correct' => $totalCorrect,
                'accuracy' => $totalAnswered > 0 ? round(($totalCorrect / $totalAnswered) * 100, 1) : 0,
                'progress' => $totalQuestions > 0 ? round(($totalAnswered / $totalQuestions) * 100, 1) : 0,
                'categories' => $categories->values(),
            ];
        });

        $totalQuestions = (int) $sections->sum('total');
        $totalAnswered = (int) $sections->sum('answered');
        $totalCorrect = (int) $sections->sum('correct');

        $totalAttempts = (int) StudentAnswer::where('student_id', $student->id)->count();
        $totalPoints = (int) ($student->wallet?->balance ?? 0);

        $recent = StudentAnswer::where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['question_id', 'is_correct', 'score_earned', 'created_at']);

        return [
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
            ],
            'totals' => [
                'total_questions' => $totalQuestions,
                'answered' => $totalAnswered,
                'correct' => $totalCorrect,
                'incorrect' => max(0, $totalAnswered - $totalCorrect),
                'attempts' => $totalAttempts,
                'accuracy' => $totalAnswered > 0 ? round(($totalCorrect / $totalAnswered) * 100, 1) : 0,
                'progress' => $totalQuestions > 0 ? round(($totalAnswered / $totalQuestions) * 100, 1) : 0,
                'points' => $totalPoints,
            ],
            'sections' => $sections->values(),
            'recent' => $recent->map(fn ($r) => [
                'question_id' => $r->question_id,
                'is_correct' => (bool) $r->is_correct,
                'score_earned' => (int) ($r->score_earned ?? 0),
                'at' => optional($r->created_at)->toISOString(),
            ])->values(),
            'bookmarks_count' => Bookmark::where('student_id', $student->id)->count(),
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'total_questions' => 0,
            'answered' => 0,
            'correct' => 0,
            'incorrect' => 0,
            'attempts' => 0,
            'accuracy' => 0,
            'progress' => 0,
            'points' => 0,
        ];
    }
}
