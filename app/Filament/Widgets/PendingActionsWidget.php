<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Models\Payment;
use App\Models\StudentDeletionRequest;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Actionable admin queue — things waiting on staff. Payments need manual
 * activation while gateways are disabled, so surfacing them here matters.
 */
class PendingActionsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $pendingPayments = Payment::where('status', 'pending')->count();
        $deletionRequests = StudentDeletionRequest::where('status', 'pending')->count();
        $openTickets = Contact::whereIn('status', ['open', 'pending'])->count();

        return [
            Stat::make('مدفوعات بانتظار التفعيل', $pendingPayments)
                ->description('تحتاج تفعيلاً يدوياً من الإدارة')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color($pendingPayments > 0 ? 'warning' : 'gray'),

            Stat::make('طلبات حذف معلّقة', $deletionRequests)
                ->description('طلبات حذف حسابات بانتظار المراجعة')
                ->descriptionIcon('heroicon-m-trash')
                ->color($deletionRequests > 0 ? 'danger' : 'gray'),

            Stat::make('تذاكر مفتوحة', $openTickets)
                ->description('رسائل تواصل تحتاج رداً')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color($openTickets > 0 ? 'info' : 'gray'),
        ];
    }
}
