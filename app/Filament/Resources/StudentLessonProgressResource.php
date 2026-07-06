<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentLessonProgressResource\Pages;
use App\Models\StudentLessonProgress;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** Read-only study-plan progress log per student/lesson. */
class StudentLessonProgressResource extends Resource
{
    protected static ?string $model = StudentLessonProgress::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $modelLabel = 'تقدم درس';
    protected static ?string $pluralModelLabel = 'تقدم الدروس';
    protected static ?string $navigationGroup = 'المحتوى التعليمي';
    protected static ?int $navigationSort = 12;

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
                Tables\Columns\TextColumn::make('lesson.title')
                    ->label('الدرس')
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'completed' => 'مكتمل',
                        'in_progress' => 'قيد التنفيذ',
                        'pending' => 'معلق',
                        default => (string) $state,
                    }),
                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('مجدول في')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('اكتمل في')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('time_spent_minutes')
                    ->label('الوقت (د)')
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'معلق',
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentLessonProgress::route('/'),
        ];
    }
}
