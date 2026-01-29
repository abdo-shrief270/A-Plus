<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Filament\Resources\CouponResource\RelationManagers;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'الدورات والمبيعات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'كوبون';

    protected static ?string $pluralModelLabel = 'الكوبونات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('تفاصيل الكوبون')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('الكود')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->autocapitalize('characters'),
                        Forms\Components\Select::make('type')
                            ->label('النوع')
                            ->options([
                                'fixed' => 'مبلغ ثابت',
                                'percentage' => 'نسبة مئوية',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('value')
                            ->label('قيمة الخصم')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('usage_limit')
                            ->label('حد الاستخدام (اختياري)')
                            ->numeric(),
                    ])->columns(2),

                Forms\Components\Section::make('الصلاحية')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label('صالح من'),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('صالح حتى'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'fixed' => 'info',
                        'percentage' => 'warning',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'fixed' => 'مبلغ ثابت',
                        'percentage' => 'نسبة مئوية',
                    }),
                Tables\Columns\TextColumn::make('value')
                    ->label('القيمة')
                    ->money('SAR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->label('صالح من')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('صالح حتى')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usages_count')
                    ->counts('usages')
                    ->label('مرات الاستخدام'),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('حد الاستخدام'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'fixed' => 'مبلغ ثابت',
                        'percentage' => 'نسبة مئوية',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsagesRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
