<?php

namespace App\Filament\Resources\LessonResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static ?string $title = 'صفحات الدرس';

    protected static ?string $modelLabel = 'صفحة';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('page_number')
                    ->label('رقم الصفحة')
                    ->numeric()
                    ->required()
                    ->default(fn() => $this->getOwnerRecord()->pages()->max('page_number') + 1),

                Forms\Components\TextInput::make('title')
                    ->label('عنوان الصفحة')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Select::make('type')
                    ->label('نوع الصفحة')
                    ->options([
                        'text' => 'نص فقط',
                        'image' => 'صورة مع شرح',
                        'question' => 'سؤال',
                        'mixed' => 'مختلط (نص + صورة)',
                    ])
                    ->required()
                    ->live()
                    ->default('text'),

                Forms\Components\Group::make()
                    ->schema([
                        // Text type
                        Forms\Components\RichEditor::make('content.body')
                            ->label('المحتوى النصي')
                            ->required()
                            ->columnSpanFull()
                            ->visible(fn(Forms\Get $get) => $get('type') === 'text'),

                        // Image type
                        Forms\Components\FileUpload::make('content.image_url')
                            ->label('الصورة')
                            ->image()
                            ->directory('lesson-pages')
                            ->required()
                            ->visible(fn(Forms\Get $get) => $get('type') === 'image'),

                        Forms\Components\Textarea::make('content.caption')
                            ->label('شرح الصورة')
                            ->rows(3)
                            ->visible(fn(Forms\Get $get) => $get('type') === 'image'),

                        // Question type
                        Forms\Components\Select::make('content.question_id')
                            ->label('السؤال')
                            ->relationship('lesson.exam.subjects.questions', 'question')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn(Forms\Get $get) => $get('type') === 'question'),

                        Forms\Components\Textarea::make('content.instructions')
                            ->label('تعليمات السؤال')
                            ->rows(2)
                            ->visible(fn(Forms\Get $get) => $get('type') === 'question'),

                        // Mixed type
                        Forms\Components\Repeater::make('content.sections')
                            ->label('الأقسام')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('النوع')
                                    ->options([
                                        'text' => 'نص',
                                        'image' => 'صورة',
                                    ])
                                    ->required()
                                    ->live(),

                                Forms\Components\RichEditor::make('content')
                                    ->label('المحتوى')
                                    ->required()
                                    ->visible(fn(Forms\Get $get) => $get('type') === 'text'),

                                Forms\Components\FileUpload::make('content')
                                    ->label('الصورة')
                                    ->image()
                                    ->directory('lesson-pages')
                                    ->required()
                                    ->visible(fn(Forms\Get $get) => $get('type') === 'image'),
                            ])
                            ->collapsible()
                            ->visible(fn(Forms\Get $get) => $get('type') === 'mixed'),
                    ]),

                Forms\Components\Toggle::make('is_required')
                    ->label('إلزامية')
                    ->default(true)
                    ->helperText('هل يجب على الطالب إكمال هذه الصفحة؟'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('page_number')
                    ->label('رقم الصفحة')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('النوع')
                    ->colors([
                        'primary' => 'text',
                        'success' => 'image',
                        'warning' => 'question',
                        'info' => 'mixed',
                    ])
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'text' => 'نص',
                        'image' => 'صورة',
                        'question' => 'سؤال',
                        'mixed' => 'مختلط',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('إلزامية')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'text' => 'نص',
                        'image' => 'صورة',
                        'question' => 'سؤال',
                        'mixed' => 'مختلط',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('page_number', 'asc')
            ->reorderable('page_number');
    }
}
