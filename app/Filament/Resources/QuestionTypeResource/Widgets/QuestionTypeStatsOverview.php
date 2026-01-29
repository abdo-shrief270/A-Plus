<?php

namespace App\Filament\Resources\QuestionTypeResource\Widgets;

use App\Models\Question;
use App\Models\QuestionType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuestionTypeStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTypes = QuestionType::count();
        $totalQuestions = Question::count();
        $avgQuestionsPerType = round($totalQuestions / max($totalTypes, 1), 1);

        return [
            Stat::make('إجمالي أنواع الأسئلة', $totalTypes)
                ->description('العدد الكلي لأنواع الأسئلة')
                ->descriptionIcon('heroicon-m-queue-list')
                ->color('success'),

            Stat::make('إجمالي الأسئلة', $totalQuestions)
                ->description('مجموع جميع الأسئلة')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('primary'),

            Stat::make('متوسط الأسئلة لكل نوع', $avgQuestionsPerType)
                ->description('متوسط عدد الأسئلة')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
