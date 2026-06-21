<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuizSessionResource\Pages;
use App\Models\QuizSession;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only view over student self-service quizzes (support/analytics).
 * Sessions are created and finalized exclusively through the student API.
 */
class QuizSessionResource extends Resource
{
    protected static ?string $model = QuizSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $modelLabel = 'اختبار ذاتي';
    protected static ?string $pluralModelLabel = 'الاختبارات الذاتية';
    protected static ?string $navigationGroup = 'المحتوى التعليمي';
    protected static ?int $navigationSort = 10;

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
                Tables\Columns\TextColumn::make('mode')
                    ->label('الوضع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'tutor' ? 'توجيه' : 'اختبار')
                    ->color(fn (string $state) => $state === 'tutor' ? 'info' : 'primary'),
                Tables\Columns\TextColumn::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'random' => 'عشوائي',
                        'unanswered' => 'غير محلولة',
                        'wrong' => 'أخطاء سابقة',
                        'bookmarked' => 'المحفوظات',
                        default => $state,
                    })
                    ->color('gray'),
                Tables\Columns\TextColumn::make('question_count')
                    ->label('عدد الأسئلة'),
                Tables\Columns\TextColumn::make('score_percent')
                    ->label('النتيجة')
                    ->suffix('%')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'expired' => 'انتهى الوقت',
                        'abandoned' => 'ملغي',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'expired' => 'danger',
                        'abandoned' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('تاريخ البدء')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('تاريخ الانتهاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'in_progress' => 'قيد التنفيذ',
                        'completed' => 'مكتمل',
                        'expired' => 'انتهى الوقت',
                        'abandoned' => 'ملغي',
                    ]),
                Tables\Filters\SelectFilter::make('mode')
                    ->label('الوضع')
                    ->options([
                        'tutor' => 'توجيه',
                        'exam' => 'اختبار',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('الطالب')
                    ->schema([
                        TextEntry::make('student.user.name')
                            ->label('الاسم')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('student.user.email')
                            ->label('البريد الإلكتروني'),
                    ])->columns(2),
                Section::make('إعدادات الاختبار')
                    ->schema([
                        TextEntry::make('mode')
                            ->label('الوضع')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => $state === 'tutor' ? 'توجيه' : 'اختبار'),
                        TextEntry::make('source')
                            ->label('المصدر')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'random' => 'عشوائي',
                                'unanswered' => 'غير محلولة',
                                'wrong' => 'أخطاء سابقة',
                                'bookmarked' => 'المحفوظات',
                                default => $state,
                            }),
                        TextEntry::make('difficulty')
                            ->label('الصعوبة')
                            ->placeholder('الكل'),
                        TextEntry::make('question_count')
                            ->label('عدد الأسئلة'),
                        TextEntry::make('time_limit_seconds')
                            ->label('المدة الزمنية')
                            ->state(fn ($record) => $record->time_limit_seconds
                                ? ($record->time_limit_seconds / 60) . ' دقيقة'
                                : 'بدون وقت'),
                        TextEntry::make('categories')
                            ->label('التصنيفات')
                            ->state(fn ($record) => \App\Models\SectionCategory::whereIn('id', $record->category_ids ?? [])->pluck('name')->join('، ') ?: '—'),
                    ])->columns(3),
                Section::make('النتيجة')
                    ->schema([
                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'in_progress' => 'قيد التنفيذ',
                                'completed' => 'مكتمل',
                                'expired' => 'انتهى الوقت',
                                'abandoned' => 'ملغي',
                                default => $state,
                            })
                            ->color(fn (string $state) => match ($state) {
                                'in_progress' => 'warning',
                                'completed' => 'success',
                                'expired' => 'danger',
                                'abandoned' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('correct_count')->label('صحيحة'),
                        TextEntry::make('incorrect_count')->label('خاطئة'),
                        TextEntry::make('skipped_count')->label('متجاهَلة'),
                        TextEntry::make('score_percent')
                            ->label('النسبة')
                            ->suffix('%')
                            ->placeholder('—'),
                    ])->columns(5),
                Section::make('التواريخ')
                    ->schema([
                        TextEntry::make('started_at')->label('البدء')->dateTime(),
                        TextEntry::make('deadline_at')->label('الموعد النهائي')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->label('الانتهاء')->dateTime()->placeholder('—'),
                    ])->columns(3),
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizSessions::route('/'),
            'view' => Pages\ViewQuizSession::route('/{record}'),
        ];
    }
}
