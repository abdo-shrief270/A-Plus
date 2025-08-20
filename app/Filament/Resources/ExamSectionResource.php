<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExamSectionResource\Pages;
use App\Filament\Resources\ExamSectionResource\RelationManagers;
use App\Models\Exam;
use App\Models\ExamSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExamSectionResource extends Resource
{
    protected static ?string $model = ExamSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 1;

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
                    ->label('اسم القسم')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
            $query->with('exam')->withCount('categories')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('القسم')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('exam.name')
                    ->label('اسم الأختبار')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('categories_count')
                    ->label('عدد الفئات')
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
                    ->label('تعديل القسم'),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف القسم'),
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
            RelationManagers\CategoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExamSections::route('/'),
            'edit' => Pages\EditExamSection::route('/{record}/edit'),
        ];
    }



    public static function getLabel(): ?string
    {
        return 'قسم';
    }
    public static function getModelLabel(): string
    {
        return 'قسم';
    }
    public static function getPluralLabel(): string
    {
        return 'أقسام';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'الأقسام';
    }
}
