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
                Tables\Filters\SelectFilter::make('platform')
                    ->label('المنصة')
                    ->options([
                        'iOS' => 'iOS',
                        'Android' => 'Android',
                        'Web' => 'Web',
                    ]),
                Tables\Filters\TernaryFilter::make('is_trusted')
                    ->label('الحالة')
                    ->placeholder('الكل')
                    ->trueLabel('موثوق')
                    ->falseLabel('محظور'),
                Tables\Filters\Filter::make('recent_login')
                    ->label('نشط مؤخراً')
                    ->query(fn(Builder $query): Builder => $query->where('last_login_at', '>=', now()->subDays(7))),
            ])
            ->actions([
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
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_trusted', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
