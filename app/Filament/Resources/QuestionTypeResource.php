<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionTypeResource\Pages;
use App\Filament\Resources\QuestionTypeResource\RelationManagers;
use App\Models\QuestionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionTypeResource extends Resource
{
    protected static ?string $model = QuestionType::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('الأسم')
                    ->required(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم المعرف')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الأسم')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الاضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ اخر تعديل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل السؤال'),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف السؤال'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
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
            'index' => Pages\ListQuestionTypes::route('/'),
            'create' => Pages\CreateQuestionType::route('/create'),
            'edit' => Pages\EditQuestionType::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'نوع سؤال';
    }
    public static function getModelLabel(): string
    {
        return 'نوع سؤال';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أنواع الأسئلة';
    }

    public static function getPluralLabel(): string
    {
        return 'أنواع الأسئلة';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'أنواع الأسئلة';
    }
}
