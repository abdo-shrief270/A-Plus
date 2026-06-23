<?php

namespace App\Filament\Exports;

use App\Models\Payment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PaymentExporter extends Exporter
{
    protected static ?string $model = Payment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('transaction_id')->label('رقم العملية'),
            ExportColumn::make('user.name')->label('المستخدم'),
            ExportColumn::make('amount')->label('المبلغ'),
            ExportColumn::make('currency')->label('العملة'),
            ExportColumn::make('payment_method')->label('طريقة الدفع'),
            ExportColumn::make('status')->label('الحالة'),
            ExportColumn::make('description')->label('الوصف'),
            ExportColumn::make('paid_at')->label('تاريخ الدفع'),
            ExportColumn::make('created_at')->label('تاريخ الإنشاء'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'اكتمل تصدير المدفوعات: ' . number_format($export->successful_rows) . ' سجل.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' فشل تصدير ' . number_format($failedRowsCount) . ' سجل.';
        }

        return $body;
    }
}
