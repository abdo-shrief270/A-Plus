<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class StudentsChart extends ChartWidget
{
    protected static ?string $heading = 'تسجيل الطلاب خلال العام';
    protected static ?int $sort = 1; // After StatsOverview

    protected function getData(): array
    {
        $data = Trend::query(User::where('type', 'student'))
            ->between(
                start: now()->startOfYear(),
                end: now(),
            )
            ->perMonth()
            ->count();

        return [
            'datasets' => [
                [
                    'label' => 'الطلاب الجدد',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10b981', // Emerald-500
                    'fill' => true,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $data->map(fn(TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
