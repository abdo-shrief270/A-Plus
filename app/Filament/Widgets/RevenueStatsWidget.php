<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $total = (float) Payment::where('status', 'paid')->sum('amount');
        $thisMonth = (float) Payment::where('status', 'paid')
            ->where('paid_at', '>=', Carbon::now()->startOfMonth())
            ->sum('amount');
        $pendingCount = Payment::where('status', 'pending')->count();
        $pendingAmount = (float) Payment::where('status', 'pending')->sum('amount');

        return [
            Stat::make('إجمالي الإيرادات', number_format($total) . ' ر.س')
                ->description('من العمليات المدفوعة')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('إيرادات هذا الشهر', number_format($thisMonth) . ' ر.س')
                ->description('منذ بداية الشهر الحالي')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),

            Stat::make('بانتظار التفعيل', $pendingCount)
                ->description(number_format($pendingAmount) . ' ر.س بانتظار تفعيل الإدارة')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'gray'),
        ];
    }
}
