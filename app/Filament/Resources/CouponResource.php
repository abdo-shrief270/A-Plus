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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'fixed' => 'Fixed Amount',
                        'percent' => 'Percentage',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('value')
                    ->label('Discount Value')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('valid_from'),
                Forms\Components\DateTimePicker::make('valid_until'),
                Forms\Components\TextInput::make('usage_limit')
                    ->numeric(),
                Forms\Components\TextInput::make('times_used')
                    ->numeric()
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usage_limit'),
                Tables\Columns\TextColumn::make('times_used'),
                Tables\Columns\TextColumn::make('created_at')
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
}
