<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'إدارة المستخدمين';

    protected static ?string $navigationLabel = 'أجهزة المستخدمين';

    protected static ?string $modelLabel = 'جهاز';

    protected static ?string $pluralModelLabel = 'الأجهزة';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الجهاز')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('المستخدم')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('device_id')
                            ->label('معرف الجهاز')
                            ->disabled()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('اسم الجهاز')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('platform')
                            ->label('المنصة')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('عنوان IP')
                            ->disabled(),
                        Forms\Components\Toggle::make('is_trusted')
                            ->label('موثوق')
                            ->helperText('إذا تم إلغاء التفعيل، لن يتمكن المستخدم من تسجيل الدخول من هذا الجهاز')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.user_name')
                    ->label('اسم المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الجهاز')
                    ->default('غير محدد')
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform')
                    ->label('المنصة')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'iOS' => 'info',
                        'Android' => 'success',
                        'Web' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('عنوان IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_trusted')
                    ->label('موثوق')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\ToggleColumn::make('is_approved')
                    ->label('مفعّل')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_current')
                    ->label('الجلسة الحالية')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخر تسجيل دخول')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'student' => 'طالب',
                        'parent' => 'ولي أمر',
                        'school' => 'مدرسة',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) return $query;
                        return $query->whereHas('user', fn($q) => $q->where('type', $data['value']));
                    }),

                Tables\Filters\SelectFilter::make('platform')
                    ->label('المنصة')
                    ->options([
                        'iOS' => 'iOS',
                        'Android' => 'Android',
                        'Web' => 'Web',
                        'web' => 'Web (lower)',
                        'ios' => 'iOS (lower)',
                        'android' => 'Android (lower)',
                    ]),

                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('التفعيل')
                    ->placeholder('الكل')
                    ->trueLabel('مفعل')
                    ->falseLabel('معلّق (بانتظار الإدارة)'),

                Tables\Filters\TernaryFilter::make('is_current')
                    ->label('الجلسة الحالية')
                    ->placeholder('الكل')
                    ->trueLabel('نشط الآن')
                    ->falseLabel('غير نشط'),

                Tables\Filters\TernaryFilter::make('is_trusted')
                    ->label('الثقة')
                    ->placeholder('الكل')
                    ->trueLabel('موثوق')
                    ->falseLabel('محظور'),

                Tables\Filters\Filter::make('pending_review')
                    ->label('بانتظار المراجعة')
                    ->query(fn(Builder $query): Builder => $query->where('is_approved', false))
                    ->toggle(),

                Tables\Filters\Filter::make('online_now')
                    ->label('متصل الآن')
                    ->query(fn(Builder $query): Builder => $query
                        ->where('is_current', true)
                        ->where('is_approved', true)
                        ->where('last_login_at', '>=', now()->subMinutes(15)))
                    ->toggle(),

                Tables\Filters\Filter::make('recent_login')
                    ->label('نشط آخر 7 أيام')
                    ->query(fn(Builder $query): Builder => $query->where('last_login_at', '>=', now()->subDays(7)))
                    ->toggle(),

                Tables\Filters\Filter::make('last_login_at')
                    ->label('آخر تسجيل دخول')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('من'),
                        Forms\Components\DatePicker::make('until')->label('إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $d) => $q->whereDate('last_login_at', '>=', $d))
                            ->when($data['until'] ?? null, fn($q, $d) => $q->whereDate('last_login_at', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $tags = [];
                        if (!empty($data['from'])) $tags[] = 'من ' . $data['from'];
                        if (!empty($data['until'])) $tags[] = 'إلى ' . $data['until'];
                        return $tags;
                    }),
            ])
            ->filtersFormColumns(2)
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('تفعيل')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn(Device $record): bool => !$record->is_approved)
                    ->requiresConfirmation()
                    ->modalHeading('تفعيل الجهاز')
                    ->modalDescription('سيتمكن المستخدم من تسجيل الدخول من هذا الجهاز. الجهاز السابق سيتم تسجيل خروجه عند أول طلب.')
                    ->action(function (Device $record): void {
                        $record->update(['is_approved' => true]);
                        activity()
                            ->causedBy(\Filament\Facades\Filament::auth()->user())
                            ->performedOn($record->user)
                            ->event('device_approved')
                            ->withProperties([
                                'device_id' => $record->device_id,
                                'name' => $record->name,
                                'platform' => $record->platform,
                            ])
                            ->log('تم تفعيل جهاز عبر لوحة الإدارة');

                        if ($record->user) {
                            $record->user->notify(new \App\Notifications\SimpleNotification(
                                title: 'تم تفعيل جهازك',
                                description: 'وافقت الإدارة على ' . ($record->name ?? 'جهازك') . '. يمكنك الآن تسجيل الدخول.',
                                link: '/dashboard/settings/security',
                                color: 'success',
                                icon: 'i-heroicons-shield-check',
                            ));
                        }
                    }),
                Tables\Actions\Action::make('toggle_trust')
                    ->label(fn(Device $record): string => $record->is_trusted ? 'حظر' : 'إلغاء الحظر')
                    ->icon(fn(Device $record): string => $record->is_trusted ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(Device $record): string => $record->is_trusted ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn(Device $record) => $record->update(['is_trusted' => !$record->is_trusted])),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve')
                        ->label('تفعيل المحدد')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['is_approved' => true])),
                    Tables\Actions\BulkAction::make('block')
                        ->label('حظر المحدد')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['is_trusted' => false])),
                    Tables\Actions\BulkAction::make('unblock')
                        ->label('إلغاء حظر المحدد')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['is_trusted' => true])),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ])
            ->defaultSort('last_login_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
//            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Show count of devices waiting for admin approval.
        return static::getModel()::where('is_approved', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
