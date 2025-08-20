<?php
namespace App\Filament\Resources\ExamSectionResource\RelationManagers;

use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;

class CategoriesRelationManager extends RelationManager
{
    protected static ?string $title = "الفئات";

    protected static ?string $icon = 'heroicon-o-rectangle-group';

    protected static string $relationship = 'categories';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('exam_section_id')
                    ->default(fn () => $this->ownerRecord->id),

                Forms\Components\TextInput::make('name')
                    ->label('اسم الفئة')
                    ->required(),

                Forms\Components\Textarea::make('description')
                    ->label('وصف الفئة')
                    ->rows(5)
                    ->required(),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->withCount('questions')
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة فئة')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function getLabel(): ?string
    {
        return 'فئة';
    }
    public static function getModelLabel(): string
    {
        return 'فئة';
    }

    public static function getPluralModelLabel(): string
    {
        return 'فئات';
    }

}
