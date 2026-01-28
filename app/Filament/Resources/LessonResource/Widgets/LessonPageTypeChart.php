<?php

namespace App\Filament\Resources\LessonResource\Widgets;

use App\Models\LessonPage;
use Filament\Widgets\ChartWidget;

class LessonPageTypeChart extends ChartWidget
{
    protected static ?string $heading = 'توزيع أنواع الصفحات';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $textPages = LessonPage::where('type', 'text')->count();
        $imagePages = LessonPage::where('type', 'image')->count();
        $questionPages = LessonPage::where('type', 'question')->count();
        $mixedPages = LessonPage::where('type', 'mixed')->count();

        return [
            'datasets' => [
                [
                    'label' => 'الصفحات',
                    'data' => [$textPages, $imagePages, $questionPages, $mixedPages],
                    'backgroundColor' => [
                        'rgb(59, 130, 246)',   // blue
                        'rgb(34, 197, 94)',    // green
                        'rgb(251, 146, 60)',   // orange
                        'rgb(168, 85, 247)',   // purple
                    ],
                ],
            ],
            'labels' => ['نص', 'صورة', 'سؤال', 'مختلط'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
