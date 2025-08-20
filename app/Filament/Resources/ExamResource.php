<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamResource\Pages;
use App\Filament\Resources\ExamResource\RelationManagers;
use App\Models\Exam;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExamResource extends Resource
{
    protected static ?string $model = Exam::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('اسم الأختبار'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('كود الأختبار')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('اسم الأختبار')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
//                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
//                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
    public static function getRelations(): array
    {
//        $record = request()->route('record');
//
//        if (!$record) {
//            return [];
//        }
//        $exam = static::getModel()::withCount('subjects')->withCount('sections')->find($record);
//        if($exam->sections_count > 0)
//        {
//            return [RelationManagers\SectionsRelationManager::class];
//        }elseif ($exam->subjects_count > 0)
//        {
//            return [RelationManagers\SubjectsRelationManager::class];
//        }else
//        {
            return [
                RelationManagers\SectionsRelationManager::class,
                RelationManagers\SubjectsRelationManager::class,
            ];
//        }

    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExams::route('/'),
            'edit' => Pages\EditExam::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'اختبار';
    }
    public static function getModelLabel(): string
    {
        return 'اختبار';
    }
    public static function getPluralLabel(): string
    {
        return 'اختبارات';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'الأختبارات';
    }
}
