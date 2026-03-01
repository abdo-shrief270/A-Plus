<?php

namespace App\Filament\Resources\QuestionResource\Widgets;

use App\Models\Question;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuestionStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected function getStats(): array
    {
        $totalQuestions = Question::count();
        $easyQuestions = Question::where('difficulty', 'easy')->count();
        $mediumQuestions = Question::where('difficulty', 'medium')->count();
        $hardQuestions = Question::where('difficulty', 'hard')->count();

        return [
            Stat::make('إجمالي الأسئلة', $totalQuestions)
                ->description('العدد الكلي للأسئلة')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('success')
                ->chart([50, 100, 150, 200, $totalQuestions]),

            Stat::make('أسئلة سهلة', $easyQuestions)
                ->description(round(($easyQuestions / max($totalQuestions, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-face-smile')
                ->color('success'),

            Stat::make('أسئلة متوسطة', $mediumQuestions)
                ->description(round(($mediumQuestions / max($totalQuestions, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('warning'),

            Stat::make('أسئلة صعبة', $hardQuestions)
                ->description(round(($hardQuestions / max($totalQuestions, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-fire')
                ->color('danger'),
        ];
    }
}
