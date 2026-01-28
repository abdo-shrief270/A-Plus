<?php

namespace App\Filament\Resources\ExamResource\Widgets;

use App\Models\Exam;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExamSubjectChart extends ChartWidget
{
    protected static ?string $heading = 'الامتحانات حسب المادة';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Count how many times each subject appears (across all exams)
        $subjectData = DB::table('exam_subjects')
            ->select('exam_subjects.name', DB::raw('count(*) as count'))
            ->groupBy('exam_subjects.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'عدد المرات',
                    'data' => $subjectData->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(34, 197, 94)',
                        'rgb(251, 146, 60)',
                        'rgb(168, 85, 247)',
                        'rgb(236, 72, 153)',
                        'rgb(14, 165, 233)',
                        'rgb(234, 179, 8)',
                        'rgb(239, 68, 68)',
                        'rgb(99, 102, 241)',
                        'rgb(20, 184, 166)',
                    ],
                ],
            ],
            'labels' => $subjectData->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
