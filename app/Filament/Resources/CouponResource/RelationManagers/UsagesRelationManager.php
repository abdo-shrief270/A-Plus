<?php

namespace App\Filament\Resources\CouponResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'usages';

    protected static ?string $title = 'الاستخدامات';

    protected static ?string $modelLabel = 'استخدام';

    protected static ?string $pluralModelLabel = 'الاستخدامات';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->label('الطالب'),
                Forms\Components\TextInput::make('discount_amount')
                    ->required()
                    ->numeric()
                    ->label('مبلغ الخصم'),
                Forms\Components\DateTimePicker::make('used_at')
                    ->default(now())
                    ->required()
                    ->label('تاريخ الاستخدام'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('الطالب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('مبلغ الخصم')
                    ->money('SAR'),
                Tables\Columns\TextColumn::make('used_at')
                    ->label('تاريخ الاستخدام')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Usages are usually created via API, but maybe admin wants to manually add?
                // Tables\Actions\CreateAction::make(), 
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
