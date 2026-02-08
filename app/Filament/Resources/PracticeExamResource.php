<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PracticeExamResource\Pages;
use App\Filament\Resources\PracticeExamResource\RelationManagers;
use App\Models\PracticeExam;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\TextEntry\TextEntrySize;

class PracticeExamResource extends Resource
{
    protected static ?string $model = PracticeExam::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $pluralModelLabel = 'النماذج';
    protected static ?string $navigationGroup = 'المحتوى التعليمي';
    protected static ?int $navigationSort = 9;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('عنوان النموذج')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان النموذج')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
                Section::make('تفاصيل النموذج')
                    ->schema([
                        TextEntry::make('title')
                            ->label('عنوان النموذج')
                            ->size(TextEntrySize::Large)
                            ->weight(FontWeight::Bold),
                        IconEntry::make('is_active')
                            ->label('الحالة')
                            ->boolean()
                            ->trueColor('success')
                            ->falseColor('danger'),
                        TextEntry::make('questions_count')
                            ->label('عدد الأسئلة')
                            ->state(fn($record) => $record->questions()?->count())
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('تاريخ الإنشاء')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime(),
                    ])->columns(3),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPracticeExams::route('/'),
            'create' => Pages\CreatePracticeExam::route('/create'),
            'view' => Pages\ViewPracticeExam::route('/{record}'),
            'edit' => Pages\EditPracticeExam::route('/{record}/edit'),
        ];
    }
}
