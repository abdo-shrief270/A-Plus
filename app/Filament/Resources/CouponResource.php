<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationGroup = 'الدورات والمبيعات';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'كوبون';

    protected static ?string $pluralModelLabel = 'كوبونات';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('كود الكوبون')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('النوع')
                    ->options([
                        'fixed' => 'مبلغ ثابت',
                        'percent' => 'نسبة مئوية',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->label('قيمة الخصم')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('valid_from')
                    ->label('صالح من'),
                Forms\Components\DateTimePicker::make('valid_until')
                    ->label('صالح حتى'),
                Forms\Components\TextInput::make('usage_limit')
                    ->label('حد الاستخدام')
                    ->numeric(),
                Forms\Components\TextInput::make('times_used')
                    ->label('مرات الاستخدام')
                    ->numeric()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('الكود')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'fixed' => 'مبلغ ثابت',
                        'percent' => 'نسبة مئوية',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'fixed',
                        'primary' => 'percent',
                    ]),
                Tables\Columns\TextColumn::make('value')
                    ->label('القيمة'),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('صالح حتى')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('حد الاستخدام'),
                Tables\Columns\TextColumn::make('times_used')
                    ->label('مرات الاستخدام'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'fixed' => 'مبلغ ثابت',
                        'percent' => 'نسبة مئوية',
                    ]),
                Tables\Filters\Filter::make('valid')
                    ->label('صالح فقط')
                    ->query(fn(Builder $query) => $query->where('valid_until', '>=', now())->orWhereNull('valid_until')),
                Tables\Filters\Filter::make('expired')
                    ->label('المنتهي')
                    ->query(fn(Builder $query) => $query->where('valid_until', '<', now())),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'كوبون';
    }

    public static function getModelLabel(): string
    {
        return 'كوبون';
    }

    public static function getPluralLabel(): string
    {
        return 'كوبونات';
    }

    public static function getTitleCasePluralModelLabel(): string
    {
        return 'الكوبونات';
    }
}
