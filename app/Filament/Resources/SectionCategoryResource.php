<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionCategoryResource\Pages;
use App\Filament\Resources\SectionCategoryResource\RelationManagers;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\SectionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SectionCategoryResource extends Resource
{
    protected static ?string $model = SectionCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('exam_section_id')
                    ->label('القسم')
                    ->searchable()
                    ->options(function () {
                        return ExamSection::query()
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('اسم الفئة')
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('الوصف')
                    ->rows(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
            $query->with('section')->withCount('questions')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('الفئة')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('الوصف')
                    ->limit(50)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('section.name')
                    ->label('اسم القسم')
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
                    ->label('تعديل الفئة'),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف الفئة'),
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
            RelationManagers\QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSectionCategories::route('/'),
            'edit' => Pages\EditSectionCategory::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'فئة';
    }
    public static function getModelLabel(): string
    {
        return 'فئة';
    }
    public static function getPluralLabel(): string
    {
        return 'فئات';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'الفئات';
    }
}
