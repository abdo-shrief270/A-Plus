<?php

namespace App\Filament\Resources\ContactResource\Widgets;

use App\Models\Contact;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContactStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي الرسائل', Contact::count())
                ->description('رسائل التواصل الواردة')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),
            Stat::make('رسائل اليوم', Contact::whereDate('created_at', today())->count())
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
