<?php

namespace App\Filament\Resources\StudentResource\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;

class StudentGenderChart extends ChartWidget
{
    protected static ?string $heading = 'توزيع الطلاب حسب الجنس';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $maleCount = Student::where('gender', 'male')->count();
        $femaleCount = Student::where('gender', 'female')->count();

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
}
