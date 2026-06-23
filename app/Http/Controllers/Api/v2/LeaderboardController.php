<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\League;
use App\Models\Student;
use App\Models\StudentScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * @tags المتصدرين (Leaderboard)
 */
class LeaderboardController extends BaseApiController
{
    private const TOP_LIMIT = 20;

    private const HISTORY_WEEKS = 8;

    /**
     * League Leaderboard (لوحة المتصدرين)
     *
     * يعيد ترتيب طلاب دوري الطالب الحالي (أسبوعياً أو كلياً)، مع ترتيب
     * الطالب نفسه وسلّم الدوريات كاملاً.
     *
     * @queryParam period string optional week (افتراضي) أو all. Example: week
     *
     * @group Leaderboard (المتصدرين)
     */
    public function index(Request $request): JsonResponse
    {
        $student = auth('api')->user()?->student;
        if (!$student) {
            return $this->errorResponse('Leaderboard is only available for students', Response::HTTP_FORBIDDEN);
        }

        $period = $request->input('period', 'week') === 'all' ? 'all' : 'week';
        // Past-week history: 0 = current week, 1 = last week, ... (week period only)
        $weekOffset = $period === 'week'
            ? max(0, min(self::HISTORY_WEEKS - 1, (int) $request->input('week_offset', 0)))
            : 0;
        $weekStart = now()->startOfWeek()->subWeeks($weekOffset);
        $weekEnd = (clone $weekStart)->endOfWeek();

        // Students created before the league backfill may have no league yet —
        // resolve to the lowest ladder rung on the fly.
        $league = $student->league
            ?? League::orderBy('min_score')->first();

        if (!$league) {
            return $this->errorResponse('لم يتم إعداد الدوريات بعد.', Response::HTTP_NOT_FOUND);
        }

        $standings = $this->standings($league->id, $period, $weekStart, $weekEnd);

        $rows = $standings->take(self::TOP_LIMIT)->values()->map(fn ($row, $i) => [
            'rank' => $i + 1,
            'student_id' => $row->student_id,
            'name' => $row->name,
            'points' => (int) $row->points,
            'is_me' => $row->student_id === $student->id,
        ]);

        $myIndex = $standings->search(fn ($row) => $row->student_id === $student->id);
        $myRow = $myIndex !== false ? $standings[$myIndex] : null;

        $ladder = League::orderBy('order')->get()->map(fn (League $l) => [
            'id' => $l->id,
            'name' => $l->name,
            'min_score' => $l->min_score,
            'color' => $l->color,
            'icon_path' => $l->icon_path,
            'order' => $l->order,
            'is_current' => $l->id === $league->id,
        ]);

        $nextLeague = League::where('min_score', '>', $league->min_score)
            ->orderBy('min_score')
            ->first();

        return $this->successResponse([
            'period' => $period,
            'league' => [
                'id' => $league->id,
                'name' => $league->name,
                'color' => $league->color,
                'icon_path' => $league->icon_path,
                'members_count' => $standings->count(),
            ],
            'me' => [
                'rank' => $myIndex !== false ? $myIndex + 1 : null,
                'points' => $myRow ? (int) $myRow->points : 0,
                'total_score' => (int) ($student->current_score ?? 0),
                'next_league' => $nextLeague ? [
                    'name' => $nextLeague->name,
                    'min_score' => $nextLeague->min_score,
                    'points_needed' => max(0, $nextLeague->min_score - (int) ($student->current_score ?? 0)),
                ] : null,
            ],
            'top' => $rows,
            'ladder' => $ladder,
            // Week selector + history (week period only).
            'week' => $period === 'week' ? [
                'offset' => $weekOffset,
                'start' => $weekStart->toDateString(),
                'end' => $weekEnd->toDateString(),
                'is_current' => $weekOffset === 0,
                'label' => $this->weekLabel($weekOffset, $weekStart, $weekEnd),
            ] : null,
            'weeks' => $period === 'week' ? $this->weekOptions() : [],
        ], 'Leaderboard retrieved successfully');
    }

    /** Human label for a week range. */
    private function weekLabel(int $offset, \Illuminate\Support\Carbon $start, \Illuminate\Support\Carbon $end): string
    {
        if ($offset === 0) {
            return 'هذا الأسبوع';
        }
        if ($offset === 1) {
            return 'الأسبوع الماضي';
        }

        return $start->toDateString() . ' - ' . $end->toDateString();
    }

    /** Selector options for the last HISTORY_WEEKS weeks (0 = current). */
    private function weekOptions(): array
    {
        $options = [];
        for ($i = 0; $i < self::HISTORY_WEEKS; $i++) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();
            $options[] = [
                'offset' => $i,
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'is_current' => $i === 0,
                'label' => $this->weekLabel($i, $start, $end),
            ];
        }

        return $options;
    }

    /**
     * Ranked members of a league. Weekly mode ranks by points earned since
     * the start of the week (ties broken by all-time score); all-time mode
     * ranks by the cached current_score.
     *
     * @return \Illuminate\Support\Collection<int, object{student_id: int, name: string, points: int|string}>
     */
    private function standings(int $leagueId, string $period, ?\Illuminate\Support\Carbon $weekStart = null, ?\Illuminate\Support\Carbon $weekEnd = null)
    {
        if ($period === 'all') {
            return Student::query()
                ->where('current_league_id', $leagueId)
                ->join('users', 'users.id', '=', 'students.user_id')
                ->where('users.active', true)
                ->orderByDesc('students.current_score')
                ->orderBy('students.id')
                ->get([
                    'students.id as student_id',
                    'users.name',
                    DB::raw('students.current_score as points'),
                ]);
        }

        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekEnd ?? (clone $weekStart)->endOfWeek();

        return Student::query()
            ->where('current_league_id', $leagueId)
            ->join('users', 'users.id', '=', 'students.user_id')
            ->where('users.active', true)
            ->leftJoinSub(
                StudentScore::query()
                    ->where('created_at', '>=', $weekStart)
                    ->where('created_at', '<=', $weekEnd)
                    ->groupBy('student_id')
                    ->select('student_id', DB::raw('SUM(score) as weekly_points')),
                'weekly',
                'weekly.student_id',
                '=',
                'students.id'
            )
            ->orderByDesc(DB::raw('COALESCE(weekly.weekly_points, 0)'))
            ->orderByDesc('students.current_score')
            ->orderBy('students.id')
            ->get([
                'students.id as student_id',
                'users.name',
                DB::raw('COALESCE(weekly.weekly_points, 0) as points'),
            ]);
    }
}
