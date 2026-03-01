<?php

namespace App\Filament\Resources\SectionCategoryResource\RelationManagers;

use App\Models\SectionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';
    protected static ?string $title = 'القطع';
    protected static ?string $modelLabel = 'قطعة';
    protected static ?string $pluralModelLabel = 'القطع';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان القطعة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('نص القطعة')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('عدد الأسئلة')
                    ->counts('questions')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة قطعة')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
