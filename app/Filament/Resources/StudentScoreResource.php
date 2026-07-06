<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentScoreResource\Pages;
use App\Models\StudentScore;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Read-only score log (league points audit). */
class StudentScoreResource extends Resource
{
    protected static ?string $model = StudentScore::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $modelLabel = 'نقاط متصدرين';
    protected static ?string $pluralModelLabel = 'سجل نقاط المتصدرين';
    protected static ?string $navigationGroup = 'إدارة المستخدمين';
    protected static ?int $navigationSort = 20;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.user.name')
                    ->label('الطالب')
                    ->searchable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('النقاط')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('المرجع')
                    ->formatStateUsing(fn (?string $state, StudentScore $record): string => $state
                        ? class_basename($state) . ' #' . $record->reference_id
                        : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentScores::route('/'),
        ];
    }
}
