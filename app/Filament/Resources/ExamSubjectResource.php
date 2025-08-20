<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamSubjectResource\Pages;
use App\Filament\Resources\ExamSubjectResource\RelationManagers;
use App\Models\Exam;
use App\Models\ExamSubject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExamSubjectResource extends Resource
{
    protected static ?string $model = ExamSubject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exam_id')
                    ->label('الأختبار')
                    ->searchable()
                    ->options(function () {
                        return Exam::query()
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('اسم المادة')
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('وصف الفئة')
                    ->rows(5)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
            $query->with('exam')->withCount('questions')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('المادة')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('exam.name')
                    ->label('اسم الأختبار')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('عدد الأسئلة')
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
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل المادة'),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف المادة'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamSubjects::route('/'),
            'edit' => Pages\EditExamSubject::route('/{record}/edit'),
        ];
    }



    public static function getLabel(): ?string
    {
        return 'مادة';
    }
    public static function getModelLabel(): string
    {
        return 'مادة';
    }
    public static function getPluralLabel(): string
    {
        return 'مواد';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'المواد';
    }
}
