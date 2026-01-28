<?php

namespace App\Filament\Resources\ExamResource\Widgets;

use App\Models\Exam;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExamStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي الاختبارات', Exam::count())
                ->description('الاختبارات المتاحة')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),
            Stat::make('إجمالي الأسئلة', \App\Models\Question::count())
                ->description('في بنك الأسئلة')
                ->color('primary'),
            Stat::make('أحدث اختبار', Exam::latest()->first()?->name ?? 'لا يوجد')
                ->color('gray'),
        ];
    }
}
