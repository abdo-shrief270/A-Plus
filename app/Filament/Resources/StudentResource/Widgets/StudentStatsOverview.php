<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي الطلاب', User::where('type', 'student')->count())
                ->description('الطلاب المسجلين')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            Stat::make('الذكور', User::where('type', 'student')->where('gender', 'male')->count())
                ->color('info'),
            Stat::make('الإناث', User::where('type', 'student')->where('gender', 'female')->count())
                ->color('danger'),
        ];
    }
}
