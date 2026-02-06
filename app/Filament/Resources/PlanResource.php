<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'الدورات والمبيعات';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'خطة أسعار';

    protected static ?string $pluralModelLabel = 'خطط الأسعار';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الخطة')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->label('النوع')
                    ->options([
                        'subscription' => 'اشتراك (Subscription)',
                        'pack' => 'باقة نقاط (Points Pack)',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('السعر')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\TextInput::make('points')
                    ->numeric()
                    ->default(0)
                    ->label('النقاط (للباقات)'),
                Forms\Components\TextInput::make('duration_days')
                    ->numeric()
                    ->label('المدة بالأيام (للاشتراكات)'),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'subscription' => 'اشتراك',
                        'pack' => 'باقة',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'subscription',
                        'success' => 'pack',
                    ]),
                Tables\Columns\TextColumn::make('price')
                    ->label('السعر')
                    ->money('SAR'),
                Tables\Columns\TextColumn::make('points')
                    ->label('النقاط'),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('نشط')
                    ->onColor('success')
                    ->offColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'subscription' => 'اشتراك',
                        'pack' => 'باقة نقاط',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة (Active)')
                    ->boolean()
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
