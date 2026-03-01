<?php

namespace App\Filament\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Wallet;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EconomyStatsWidget extends BaseWidget
{
    use HasWidgetShield;
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Active Subscriptions', Subscription::active()->count())
                ->description('Students currently subscribed')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Total Points in Wallets', Wallet::sum('balance'))
                ->description('Outstanding liability')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),

            Stat::make('Active Plans', Plan::where('is_active', true)->count())
                ->description('Available for purchase')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),
        ];
    }
}
