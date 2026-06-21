<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SectionCategory;
use App\Models\StudentAnswer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags الأداء (Performance Analytics)
 *
 * Trend + weak-spot analytics derived from the student's answer history.
 */
class PerformanceController extends BaseApiController
{
    /** Minimum attempts before a category counts as a reliable weak/strong spot. */
    private const MIN_ATTEMPTS = 3;

    /**
     * My Performance (تحليل أدائي)
     *
     * يعيد اتجاه الدقّة عبر آخر فترة، وأضعف وأقوى التصنيفات، وملخصاً عاماً —
     * لمساعدة الطالب على معرفة نقاط ضعفه والتدرّب عليها.
     *
     * @queryParam days integer optional نافذة الاتجاه بالأيام. Default 14.
     *
     * @group Performance (الأداء)
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Performance is only available for students', Response::HTTP_FORBIDDEN);
        }

        $days = min(60, max(7, (int) $request->input('days', 14)));
        $since = Carbon::today()->subDays($days - 1);

        return $this->successResponse([
            'accuracy_trend' => $this->accuracyTrend($student->id, $since, $days),
            'categories' => $this->categoryBreakdown($student),
            'totals' => $this->totals($student->id),
        ], 'Performance retrieved successfully');
    }

    /** Daily answered/correct/accuracy across the window (zero-filled). */
    private function accuracyTrend(int $studentId, Carbon $since, int $days): array
    {
        $rows = DB::table('student_answers')
            ->where('student_id', $studentId)
            ->whereDate('created_at', '>=', $since->toDateString())
            ->groupBy('day')
            ->select(
                DB::raw('DATE(created_at) as day'),
                DB::raw('COUNT(*) as answered'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct')
            )
            ->get()
            ->keyBy('day');

        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $since->copy()->addDays($i)->toDateString();
            $row = $rows[$date] ?? null;
            $answered = (int) ($row->answered ?? 0);
            $correct = (int) ($row->correct ?? 0);
            $trend[] = [
                'date' => $date,
                'answered' => $answered,
                'correct' => $correct,
                'accuracy' => $answered > 0 ? round($correct / $answered * 100, 1) : null,
            ];
        }

        return $trend;
    }

    /**
     * Per-category accuracy for the student's exam, split into weakest and
     * strongest (only categories with enough attempts to be meaningful).
     */
    private function categoryBreakdown($student): array
    {
        $categoryIds = SectionCategory::whereHas('section', fn ($s) => $s->where('exam_id', $student->exam_id))
            ->pluck('id');

        $agg = DB::table('student_answers as sa')
            ->join('category_questions as cq', 'cq.question_id', '=', 'sa.question_id')
            ->join('section_categories as sc', 'sc.id', '=', 'cq.section_category_id')
            ->join('exam_sections as es', 'es.id', '=', 'sc.exam_section_id')
            ->where('sa.student_id', $student->id)
            ->whereIn('cq.section_category_id', $categoryIds)
            ->groupBy('cq.section_category_id', 'sc.name', 'es.name')
            ->select(
                'cq.section_category_id as category_id',
                'sc.name as category_name',
                'es.name as section_name',
                DB::raw('COUNT(DISTINCT sa.question_id) as answered'),
                DB::raw('COUNT(DISTINCT CASE WHEN sa.is_correct = 1 THEN sa.question_id END) as correct')
            )
            ->get()
            ->map(fn ($r) => [
                'category_id' => (int) $r->category_id,
                'name' => $r->category_name,
                'section' => $r->section_name,
                'answered' => (int) $r->answered,
                'accuracy' => $r->answered > 0 ? round($r->correct / $r->answered * 100, 1) : 0.0,
            ])
            ->filter(fn ($c) => $c['answered'] >= self::MIN_ATTEMPTS)
            ->values();

        $sorted = $agg->sortBy('accuracy')->values();

        return [
            'weakest' => $sorted->take(5)->all(),
            'strongest' => $sorted->reverse()->take(5)->values()->all(),
            'evaluated_count' => $sorted->count(),
        ];
    }

    private function totals(int $studentId): array
    {
        $answered = StudentAnswer::where('student_id', $studentId)->count();
        $correct = StudentAnswer::where('student_id', $studentId)->where('is_correct', true)->count();

        return [
            'answered' => $answered,
            'correct' => $correct,
            'accuracy' => $answered > 0 ? round($correct / $answered * 100, 1) : 0.0,
        ];
    }
}
