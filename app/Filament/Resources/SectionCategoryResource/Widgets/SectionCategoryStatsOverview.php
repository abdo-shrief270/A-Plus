<?php

namespace App\Filament\Resources\SectionCategoryResource\Widgets;

use App\Models\SectionCategory;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SectionCategoryStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $totalCategories = SectionCategory::count();
        $categoriesWithSections = SectionCategory::has('section')->count();

        return [
            Stat::make('إجمالي التصنيفات', $totalCategories)
                ->description('العدد الكلي للتصنيفات')
                ->descriptionIcon('heroicon-m-folder')
                ->color('success'),

            Stat::make('تصنيفات مرتبطة بأقسام', $categoriesWithSections)
                ->description(round(($categoriesWithSections / max($totalCategories, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
        ];
    }
}
