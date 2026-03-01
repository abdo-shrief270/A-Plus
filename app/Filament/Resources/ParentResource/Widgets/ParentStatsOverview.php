<?php

namespace App\Filament\Resources\ParentResource\Widgets;

use App\Models\Parentt;
use App\Models\StudentParent;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParentStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $totalParents = Parentt::count();
        $activeParents = Parentt::where('active', true)->count();
        $parentsWithStudents = StudentParent::distinct('parent_id')->count('parent_id');

        return [
            Stat::make('إجمالي أولياء الأمور', $totalParents)
                ->description('العدد الكلي لأولياء الأمور')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([10, 20, 30, 40, $totalParents]),

            Stat::make('أولياء الأمور النشطون', $activeParents)
                ->description(round(($activeParents / max($totalParents, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),

            Stat::make('لديهم طلاب', $parentsWithStudents)
                ->description('أولياء أمور مرتبطون بطلاب')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
        ];
    }
}
