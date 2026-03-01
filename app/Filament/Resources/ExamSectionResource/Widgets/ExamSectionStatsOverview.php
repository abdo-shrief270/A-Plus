<?php

namespace App\Filament\Resources\ExamSectionResource\Widgets;

use App\Models\ExamSection;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Illuminate\Support\Facades\DB;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExamSectionStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $totalSections = ExamSection::count();
        $sectionsWithExams = ExamSection::has('exam')->count();

        return [
            Stat::make('إجمالي الأقسام', $totalSections)
                ->description('العدد الكلي للأقسام')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('success'),

            Stat::make('أقسام مرتبطة بامتحانات', $sectionsWithExams)
                ->description(round(($sectionsWithExams / max($totalSections, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
        ];
    }
}
