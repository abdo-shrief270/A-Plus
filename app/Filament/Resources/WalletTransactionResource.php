<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only wallet ledger — transactions are written exclusively by the
 * payment/answer flows; admins audit, never edit.
 */
class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $modelLabel = 'حركة نقاط';
    protected static ?string $pluralModelLabel = 'سجل النقاط';
    protected static ?string $navigationGroup = 'المالية';
    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wallet.student.user.name')
                    ->label('الطالب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('المقدار')
                    ->sortable()
                    ->color(fn ($state): string => (int) $state < 0 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state): string => ((int) $state > 0 ? '+' : '') . (int) $state),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'subscription_grant' => 'success',
                        'pack_purchase' => 'info',
                        'question_answer', 'content_view' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'subscription_grant' => 'نقاط اشتراك',
                        'pack_purchase' => 'شراء باقة',
                        'question_answer' => 'حلّ سؤال',
                        'content_view' => 'فتح محتوى',
                        'deposit' => 'إيداع',
                        'withdraw' => 'سحب',
                        default => (string) $state,
                    }),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('المرجع')
                    ->formatStateUsing(fn (?string $state, WalletTransaction $record): string => $state
                        ? class_basename($state) . ' #' . $record->reference_id
                        : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'subscription_grant' => 'نقاط اشتراك',
                        'pack_purchase' => 'شراء باقة',
                        'question_answer' => 'حلّ سؤال',
                        'content_view' => 'فتح محتوى',
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
        ];
    }
}
