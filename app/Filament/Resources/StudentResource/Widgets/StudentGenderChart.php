<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Models\Student;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class StudentGenderChart extends ChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'توزيع الطلاب حسب الجنس';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $maleCount = Student::whereHas('user', function ($query) {
            $query->where('gender', 'male');
        })->count();

        $femaleCount = Student::whereHas('user', function ($query) {
            $query->where('gender', 'female');
        })->count();

        return [
            'datasets' => [
                [
                    'label' => 'الطلاب',
                    'data' => [$maleCount, $femaleCount],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(251, 146, 60)',
                    ],
                ],
            ],
            'labels' => ['ذكور', 'إناث'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
