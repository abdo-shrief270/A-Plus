<?php

namespace App\Filament\Resources\AdminResource\Widgets;

use App\Models\Admin;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $totalAdmins = Admin::count();
        $activeAdmins = Admin::where('active', true)->count();
        $superAdmins = Admin::role('مدير النظام')->count();

        return [
            Stat::make('إجمالي المديرين', $totalAdmins)
                ->description('العدد الكلي للمديرين')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([5, 10, 15, 20, 25, $totalAdmins]),

            Stat::make('المديرين النشطون', $activeAdmins)
                ->description(round(($activeAdmins / max($totalAdmins, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary')
                ->chart([3, 7, 12, 18, $activeAdmins]),

            Stat::make('مديرو النظام', $superAdmins)
                ->description('المديرون بصلاحيات كاملة')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),
        ];
    }
}
