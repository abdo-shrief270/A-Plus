<?php

namespace App\Filament\Resources\Shared\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only timeline of activity_log entries for the current user record.
 * Attached to Student / Parent / School resources so admins can audit
 * exactly what happened on the account.
 */
class UserActivityLogRelationManager extends RelationManager
{
    protected static string $relationship = 'userActivities';

    protected static ?string $title = 'سجل النشاط';

    protected static ?string $modelLabel = 'حدث';
    protected static ?string $pluralModelLabel = 'سجل النشاط';

    protected static ?string $recordTitleAttribute = 'description';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'login_success' => 'success',
                        'login_failed' => 'danger',
                        'new_device_attempt' => 'warning',
                        'device_approved' => 'info',
                        'enable_2fa', 'disable_2fa' => 'warning',
                        'verify_email', 'verify_phone', 'verify_whatsapp' => 'success',
                        'payment_succeeded' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'login_success' => 'دخول ناجح',
                        'login_failed' => 'دخول فاشل',
                        'new_device_attempt' => 'جهاز جديد',
                        'device_approved' => 'تفعيل جهاز',
                        'enable_2fa' => 'تفعيل 2FA',
                        'disable_2fa' => 'إيقاف 2FA',
                        'verify_email' => 'تأكيد بريد',
                        'verify_phone' => 'تأكيد هاتف',
                        'verify_whatsapp' => 'تأكيد واتساب',
                        'payment_succeeded' => 'دفع ناجح',
                        default => $state ?? 'حدث',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->wrap()
                    ->limit(80),
                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('properties.device_id')
                    ->label('الجهاز')
                    ->placeholder('—')
                    ->limit(16)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('من قبل')
                    ->placeholder('النظام')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('متى')
                    ->dateTime('Y-m-d H:i')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('النوع')
                    ->options([
                        'login_success' => 'دخول ناجح',
                        'login_failed' => 'دخول فاشل',
                        'new_device_attempt' => 'جهاز جديد',
                        'device_approved' => 'تفعيل جهاز',
                        'enable_2fa' => 'تفعيل 2FA',
                        'disable_2fa' => 'إيقاف 2FA',
                        'verify_email' => 'تأكيد بريد',
                        'verify_phone' => 'تأكيد هاتف',
                        'verify_whatsapp' => 'تأكيد واتساب',
                        'payment_succeeded' => 'دفع ناجح',
                    ]),
            ])
            ->paginated([10, 25, 50]);
    }
}
