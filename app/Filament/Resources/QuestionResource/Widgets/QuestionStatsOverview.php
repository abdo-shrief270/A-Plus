<?php

namespace App\Filament\Resources\QuestionResource\Widgets;

use App\Models\Question;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuestionStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('إجمالي الأسئلة', Question::count())
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('success'),
            Stat::make('أسئلة نصية', Question::whereHas('type', fn($q) => $q->where('name', 'نصي'))->count())
                ->color('primary'),
            Stat::make('أسئلة صورية', Question::whereHas('type', fn($q) => $q->where('name', 'صوري'))->count())
                ->color('info'),
        ];
    }
}
