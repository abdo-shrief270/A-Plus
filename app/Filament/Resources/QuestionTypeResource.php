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
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class QuestionTypeResource extends Resource
{
    protected static ?string $model = QuestionType::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 7;
    protected static ?string $navigationGroup = 'المحتوى التعليمي';

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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات نوع السؤال')
                    ->schema([
                        TextEntry::make('name')
                            ->label('الأسم'),
                        TextEntry::make('id')
                            ->label('رقم المعرف'),
                    ])
                    ->columns(2),

                Section::make('معلومات النظام')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('تاريخ الاضافة')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('تاريخ اخر تعديل')
                            ->dateTime(),
                    ])
                    ->columns(2),
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
            'view' => Pages\ViewQuestionType::route('/{record}'),
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
