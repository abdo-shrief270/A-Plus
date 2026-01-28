<?php

namespace App\Filament\Resources\ExamResource\Widgets;

use App\Models\Exam;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExamSubjectChart extends ChartWidget
{
    protected static ?string $heading = 'الامتحانات حسب المادة';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Count subjects and their exams
        $subjectData = DB::table('exam_subjects')
            ->select('exam_subjects.name', DB::raw('count(exam_subjects.id) as count'))
            ->groupBy('exam_subjects.id', 'exam_subjects.name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'عدد المواد',
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
