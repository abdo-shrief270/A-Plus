<?php
namespace App\Filament\Resources\ExamResource\RelationManagers;

use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;

class SectionsRelationManager extends RelationManager
{
    protected static ?string $title = "الأقسام";

    protected static ?string $icon = 'heroicon-o-rectangle-group';

    protected static string $relationship = 'sections';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('exam_id')
                    ->default(fn () => $this->ownerRecord->id),

                Forms\Components\TextInput::make('name')
                    ->label('اسم القسم')
                    ->required(),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->withCount('categories')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('القسم')
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة قسم')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getLabel(): ?string
    {
        return 'قسم';
    }
    public static function getModelLabel(): string
    {
        return 'قسم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'اقسام';
    }

}
