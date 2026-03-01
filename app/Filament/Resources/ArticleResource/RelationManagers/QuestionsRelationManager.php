<?php

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Models\QuestionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';
    protected static ?string $title = 'الأسئلة';
    protected static ?string $modelLabel = 'سؤال';
    protected static ?string $pluralModelLabel = 'الأسئلة';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('question_type_id')
                    ->label('نوع السؤال')
                    ->options(function () {
                        return QuestionType::query()
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $type = QuestionType::find($state);
                        if ($type && $type->name == 'مقارنة') {
                            $set('answers', [
                                ['text' => 'القيمة الأولى أكبر', 'is_correct' => false, 'order' => 1],
                                ['text' => 'القيمة الثانية أكبر', 'is_correct' => false, 'order' => 2],
                                ['text' => 'القيمتان متساويتان', 'is_correct' => false, 'order' => 3],
                                ['text' => 'المعطيات غير كافية', 'is_correct' => false, 'order' => 4],
                            ]);
                        } elseif ($type && ($type->name == 'نصي' || $type->name == 'صوري')) {
                            $set('answers', [
                                ['text' => '', 'is_correct' => false, 'order' => 1],
                                ['text' => '', 'is_correct' => false, 'order' => 2],
                                ['text' => '', 'is_correct' => false, 'order' => 3],
                                ['text' => '', 'is_correct' => false, 'order' => 4],
                            ]);
                        }
                    })
                    ->required(),

                Forms\Components\Textarea::make('text')
                    ->label('نص السؤال')
                    ->rows(5)
                    ->required(),

                Forms\Components\FileUpload::make('image_path')
                    ->label('صورة مرفقة')
                    ->image()
                    ->imageEditor()
                    ->directory('question_images')
                    ->disk('public')
                    ->imageEditorEmptyFillColor('#000000')
                    ->previewable(false)
                    ->moveFiles(),

                Forms\Components\Textarea::make('explanation_text')
                    ->label('شرح السؤال')
                    ->rows(5),

                Forms\Components\FileUpload::make('explanation_text_image_path')
                    ->label('صورة مرفقة لشرح السؤال')
                    ->image()
                    ->imageEditor()
                    ->disk('public')
                    ->imageEditorEmptyFillColor('#000000')
                    ->moveFiles()
                    ->previewable(false)
                    ->directory('question_explanation_images'),

                Forms\Components\TextInput::make('explanation_video_url')
                    ->label('فيديو شرح السؤال')
                    ->url()
                    ->columnSpanFull()
                    ->activeUrl(),

                Forms\Components\Repeater::make('answers')
                    ->label('الإجابات')
                    ->columnSpanFull()
                    ->relationship('answers')
                    ->orderColumn('order')
                    ->schema([
                        Forms\Components\TextInput::make('text')
                            ->label('نص الإجابة')
                            ->columnSpan(2)
                            ->distinct()
                            ->live()
                            ->helperText('تكرار الأجابة غير مسموح')
                            ->required(),

                        Forms\Components\Toggle::make('is_correct')
                            ->label('الإجابة صحيحة؟')
                            ->inline(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Components\Component $component, $state, Forms\Get $get, Forms\Set $set) {
                                if ($state) {
                                    $answers = $get('../../answers');
                                    preg_match('/answers\.([^.]+)\.is_correct/', $component->getId(), $matches);
                                    $id = $matches[1] ?? null;
                                    foreach ($answers as $key => &$value) {
                                        if ($key == $id)
                                            continue;
                                        $value['is_correct'] = false;
                                    }
                                    $set('../../answers', $answers);
                                }
                            })
                            ->required(),
                    ])
                    ->itemLabel(function (array $state, callable $get, string $uuid) {
                        $index = array_search($uuid, array_keys($get('answers') ?? []));
                        return 'إجابة ' . ($index + 1);
                    })
                    ->defaultItems(4)
                    ->minItems(4)
                    ->maxItems(4)
                    ->columns(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type.name')
                    ->label('النوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('text')
                    ->label('نص السؤال')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->text),
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('صورة')
                    ->square(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['id', 'text']),
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn($record) => \App\Filament\Resources\QuestionResource::getUrl('view', ['record' => $record])),
                Tables\Actions\DetachAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
