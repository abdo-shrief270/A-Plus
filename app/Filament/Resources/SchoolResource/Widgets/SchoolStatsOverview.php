<?php

namespace App\Filament\Resources\SchoolResource\Widgets;

use App\Models\School;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي المدارس', School::count())
                ->description('عدد المدارس المسجلة')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('success'),
            Stat::make('مدارس نشطة', School::where('active', true)->count())
                ->description('المدارس المفعلة')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
            Stat::make('مدارس جديدة', School::where('created_at', '>=', now()->subMonth())->count())
                ->description('خلال آخر 30 يوم')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];
    }
}
