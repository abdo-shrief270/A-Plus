<?php

namespace App\Filament\Resources\ExamResource\Widgets;

use App\Models\Exam;
use App\Models\Question;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExamStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalExams = Exam::count();
        $activeExams = Exam::where('active', true)->count();
        $totalQuestions = Question::count();
        $avgQuestionsPerExam = round(Question::count() / max(Exam::count(), 1), 1);

        return [
            Stat::make('إجمالي الامتحانات', $totalExams)
                ->description('العدد الكلي للامتحانات')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('success')
                ->chart([3, 7, 12, 18, 22, $totalExams]),

            Stat::make('الامتحانات النشطة', $activeExams)
                ->description(round(($activeExams / max($totalExams, 1)) * 100, 1) . '% من الإجمالي')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),

            Stat::make('إجمالي الأسئلة', $totalQuestions)
                ->description('في جميع الامتحانات')
                ->descriptionIcon('heroicon-m-question-mark-circle')
                ->color('info')
                ->chart([50, 100, 150, 200, $totalQuestions]),

            Stat::make('متوسط الأسئلة', $avgQuestionsPerExam)
                ->description('لكل امتحان')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),
        ];
    }
}
