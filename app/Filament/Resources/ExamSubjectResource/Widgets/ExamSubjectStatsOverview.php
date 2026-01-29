<?php

namespace App\Filament\Resources\ExamSubjectResource\Widgets;

use App\Models\ExamSubject;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExamSubjectStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalSubjects = ExamSubject::count();
        $uniqueSubjects = ExamSubject::distinct('name')->count('name');
        $subjectsWithExams = ExamSubject::has('exam')->count();

        return [
            Stat::make('إجمالي المواد', $totalSubjects)
                ->description('العدد الكلي للمواد')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),

            Stat::make('مواد فريدة', $uniqueSubjects)
                ->description('عدد المواد المختلفة')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('مواد مرتبطة بامتحانات', $subjectsWithExams)
                ->description(round(($subjectsWithExams / max($totalSubjects, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),
        ];
    }
}
