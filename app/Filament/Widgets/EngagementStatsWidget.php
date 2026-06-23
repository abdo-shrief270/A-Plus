<?php

namespace App\Filament\Widgets;

use App\Models\QuizSession;
use App\Models\StudentAnswer;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EngagementStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $weekAgo = Carbon::now()->subDays(7);

        $answered = StudentAnswer::count();
        $quizzes = QuizSession::where('status', QuizSession::STATUS_COMPLETED)->count();
        $activeStudents = StudentAnswer::where('created_at', '>=', $weekAgo)
            ->distinct('student_id')
            ->count('student_id');

        return [
            Stat::make('إجمالي الإجابات', number_format($answered))
                ->description('أسئلة أجاب عنها الطلاب')
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('primary'),

            Stat::make('اختبارات مكتملة', number_format($quizzes))
                ->description('جلسات اختبار ذاتي مكتملة')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('طلاب نشطون (٧ أيام)', number_format($activeStudents))
                ->description('طلاب أجابوا خلال الأسبوع الأخير')
                ->descriptionIcon('heroicon-m-bolt')
                ->color('info'),
        ];
    }
}
