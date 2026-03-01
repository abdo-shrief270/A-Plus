<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Resources\ArticleResource\RelationManagers;
use App\Models\Article;
use App\Models\ExamSection;
use App\Models\SectionCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'قطعة';
    protected static ?string $pluralModelLabel = 'القطع';
    protected static ?string $navigationGroup = 'المحتوى التعليمي';
    protected static ?int $navigationSort = 8;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('section_category_id')
                            ->label('الفئة')
                            ->searchable()
                            ->required()
                            ->options(function () {
                                return SectionCategory::with('section.exam')
                                    ->get()
                                    ->mapWithKeys(function ($cat) {
                                        $label = ($cat->section?->exam?->name ?? '') . ' > ' . ($cat->section?->name ?? '') . ' > ' . $cat->name;
                                        return [$cat->id => $label];
                                    });
                            }),
                        Forms\Components\TextInput::make('title')
                            ->label('عنوان القطعة')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('content')
                            ->label('نص القطعة')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn(Builder $query) => $query->with('category.section.exam')->withCount('questions')
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('القطعة')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn($record) => $record->title),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('الفئة')
                    ->description(fn ($record) => $record->category?->section?->name)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('عدد الأسئلة')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة')
                    ->boolean()
                    ->trueLabel('نشط')
                    ->falseLabel('غير نشط')
                    ->placeholder('الكل'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل'),
                Tables\Actions\DeleteAction::make()
                    ->label('حذف'),
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
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
