<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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

                Forms\Components\Toggle::make('auto_fill_answers')
                    ->label('سؤال مقارنات')
                    ->inline(false)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('answers', [
                                ['text' => 'القيمة الأولي أكبر','is_correct'=>false,'order'=>1],
                                ['text' => 'القيمة الثانية أكبر','is_correct'=>false,'order'=>2],
                                ['text' => 'المعطيات غير كافية','is_correct'=>false,'order'=>3],
                                ['text' => 'القيمتان متساويتان','is_correct'=>false,'order'=>4],
                            ]);
                        } else {
                            $set('answers', []); // clear if disabled
                        }
                    }),

                Forms\Components\Repeater::make('answers')
                    ->label('الإجابات')
                    ->columnSpanFull()
                    ->relationship('answers') // assumes Question has answers() relation
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
//                            ->boolean() // true/false
                            ->inline(false) // nicer UI
                            ->live()
                            ->afterStateUpdated(function (Forms\Components\Component $component,$state,Forms\Get $get,Forms\Set $set){
                                if($state)
                                {
                                    $answers=$get('../../answers');
                                    preg_match('/answers\.([^.]+)\.is_correct/', $component->getId(), $matches);
                                    $id = $matches[1] ?? null;
                                    foreach($answers as $key=> &$value){
                                        if($key==$id)
                                            continue;
                                        $value['is_correct']=false;
                                    }
                                    $set('../../answers',$answers);
//                                dd($state,$component->getId(),$answers,$matches);
                                }

                            })
                            ->required(),
                    ])
                    ->itemLabel(function (array $state, callable $get, string $uuid) {
                        $index = array_search($uuid, array_keys($get('answers') ?? []));
                        return 'إجابة ' . ($index + 1); // e.g., "Answer 1", "Answer 2"
                    })

//                    ->orderColumn('order')
//                    ->reorderableWithButtons()
//                    ->reorderableWithDragAndDrop(false)
                    ->defaultItems(4)
                    ->minItems(4)
                    ->maxItems(4)
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم السؤال')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('text')
                    ->label('نص السؤال')
                    ->limit(50)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('image_path')
                    ->label('صورة مرفقة')
                    ->formatStateUsing(fn ($state) => $state ? '<img src="' . Storage::url($state) . '" style="max-height: 50px; max-width: 50px;">' : 'No Image')
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('explanation_text')
                    ->label('شرح السؤال')
                    ->limit(50)
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('explanation_text_image_path')
                    ->label('صورة مرفقة لشرح السؤال')
                    ->formatStateUsing(fn ($state) => $state ? '<img src="' . Storage::url($state) . '" style="max-height: 50px; max-width: 50px;">' : 'No Image')
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('explanation_video_url')
                    ->label('فيديو شرح السؤال')
                    ->url(fn ($record) => $record->explanation_video_url, true)
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
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل السؤال'),

                Tables\Actions\DeleteAction::make()
                    ->label('حذف السؤال'),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }

    public static function getLabel(): ?string
    {
        return 'سؤال';
    }
    public static function getModelLabel(): string
    {
        return 'سؤال';
    }

    public static function getPluralModelLabel(): string
    {
        return 'أسئلة';
    }

    public static function getPluralLabel(): string
    {
        return 'أسئلة';
    }
    public static function getTitleCasePluralModelLabel(): string
    {
        return 'الأسئلة';
    }
}
