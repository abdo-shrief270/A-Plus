<?php

namespace App\Filament\Widgets;

use App\Models\Exam;
use App\Models\Question;
use App\Models\School;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('الطلاب', User::where('type', 'student')->count())
                ->description('إجمالي عدد الطلاب المسجلين')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            Stat::make('المدارس', School::count())
                ->description('إجمالي عدد المدارس المسجلة')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),
            Stat::make('الاختبارات', Exam::count())
                ->description('إجمالي عدد الاختبارات المتاحة')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('warning'),
            Stat::make('الأسئلة', Question::count())
                ->description('إجمالي عدد الأسئلة في بنك المعلومات')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('info'),
        ];
    }
}
