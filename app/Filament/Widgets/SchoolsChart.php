<?php

namespace App\Filament\Widgets;

use App\Models\School;
use Filament\Widgets\ChartWidget;

class SchoolsChart extends ChartWidget
{
    protected static ?string $heading = 'أعلى المدارس تسجيلاً';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $schools = School::withCount('studentSchool')
            ->orderByDesc('student_school_count')
            ->take(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'عدد الطلاب',
                    'data' => $schools->pluck('student_school_count'),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#8b5cf6',
                        '#ec4899',
                        '#f97316',
                        '#eab308'
                    ],
                ],
            ],
            'labels' => $schools->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
