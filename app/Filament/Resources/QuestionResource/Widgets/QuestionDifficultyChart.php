<?php

namespace App\Filament\Resources\QuestionResource\Widgets;

use App\Models\Question;
use Filament\Widgets\ChartWidget;

class QuestionDifficultyChart extends ChartWidget
{
    protected static ?string $heading = 'توزيع صعوبة الأسئلة';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $easyCount = Question::where('difficulty', 'easy')->count();
        $mediumCount = Question::where('difficulty', 'medium')->count();
        $hardCount = Question::where('difficulty', 'hard')->count();

        return [
            'datasets' => [
                [
                    'label' => 'الأسئلة',
                    'data' => [$easyCount, $mediumCount, $hardCount],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',    // green
                        'rgb(251, 146, 60)',   // orange
                        'rgb(239, 68, 68)',    // red
                    ],
                ],
            ],
            'labels' => ['سهل', 'متوسط', 'صعب'],
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
