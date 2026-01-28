<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalStudents = Student::count();
        $activeStudents = Student::whereHas('exam')->count();

        $maleStudents = Student::whereHas('user', function ($query) {
            $query->where('gender', 'male');
        })->count();

        $femaleStudents = Student::whereHas('user', function ($query) {
            $query->where('gender', 'female');
        })->count();

        return [
            Stat::make('إجمالي الطلاب', $totalStudents)
                ->description('العدد الكلي للطلاب المسجلين')
                ->descriptionIcon('heroicon-m-users')
                ->color('success')
                ->chart([7, 12, 18, 25, 30, 35, $totalStudents]),

            Stat::make('الطلاب النشطون', $activeStudents)
                ->description('الطلاب المسجلون في امتحانات')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary')
                ->chart([5, 10, 15, 20, 25, $activeStudents]),

            Stat::make('الطلاب الذكور', $maleStudents)
                ->description(round(($maleStudents / max($totalStudents, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),

            Stat::make('الطالبات', $femaleStudents)
                ->description(round(($femaleStudents / max($totalStudents, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-user')
                ->color('warning'),
        ];
    }
}
