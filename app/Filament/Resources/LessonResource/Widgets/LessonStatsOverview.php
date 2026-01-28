<?php

namespace App\Filament\Resources\LessonResource\Widgets;

use App\Models\Lesson;
use App\Models\LessonPage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LessonStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalLessons = Lesson::count();
        $activeLessons = Lesson::where('active', true)->count();
        $totalPages = LessonPage::count();
        $avgPagesPerLesson = round(LessonPage::count() / max(Lesson::count(), 1), 1);

        return [
            Stat::make('إجمالي الدروس', $totalLessons)
                ->description('العدد الكلي للدروس')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success')
                ->chart([5, 10, 15, 20, $totalLessons]),

            Stat::make('الدروس النشطة', $activeLessons)
                ->description(round(($activeLessons / max($totalLessons, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),

            Stat::make('إجمالي الصفحات', $totalPages)
                ->description('في جميع الدروس')
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('info')
                ->chart([20, 40, 60, 80, $totalPages]),

            Stat::make('متوسط الصفحات', $avgPagesPerLesson)
                ->description('لكل درس')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('warning'),
        ];
    }
}
