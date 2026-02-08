<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use App\Models\QuestionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Grid;
use Illuminate\Support\Facades\Storage;
use App\Filament\Exports\QuestionExporter;
use App\Filament\Imports\QuestionImporter;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Filament\Resources\Components\Tab;
use App\Filament\Resources\QuestionResource\Widgets\QuestionStatsOverview;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 7;
    protected static ?string $navigationGroup = 'المحتوى التعليمي';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Question Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('السؤال')
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
                                    ->previewable(true)
                                    ->moveFiles()
                                    ->formatStateUsing(fn($state, $record) => $record?->getRawOriginal('image_path') ? [$record->getRawOriginal('image_path')] : null),
                                Forms\Components\Toggle::make('is_new')
                                    ->label('سؤال جديد (Trending)')
                                    ->default(false),
                                Forms\Components\Select::make('practice_exam_id')
                                    ->label('النموذج')
                                    ->relationship('practiceExam', 'title')
                                    ->searchable()
                                    ->preload(),
                            ]),
                        Forms\Components\Tabs\Tab::make('الشرح')
                            ->schema([
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
                                    ->previewable(true)
                                    ->directory('question_explanation_images')
                                    ->formatStateUsing(fn($state, $record) => $record?->getRawOriginal('explanation_text_image_path') ? [$record->getRawOriginal('explanation_text_image_path')] : null),
                                Forms\Components\TextInput::make('explanation_video_url')
                                    ->label('فيديو شرح السؤال')
                                    ->url()
                                    ->activeUrl(),
                            ]),
                        Forms\Components\Tabs\Tab::make('الإجابات')
                            ->schema([
                                Forms\Components\Repeater::make('answers')
                                    ->label('الإجابات')
                                    ->relationship('answers')
                                    ->schema([
                                        Forms\Components\TextInput::make('text')
                                            ->label('نص الإجابة')
                                            ->columnSpan(2)
                                            ->distinct()
                                            ->live()
                                            ->visible(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'نصي' || QuestionType::find($get('../../question_type_id'))?->name == 'مقارنة')
                                            ->disabled(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'مقارنة')
                                            ->required(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'نصي'),

                                        Forms\Components\FileUpload::make('image_path')
                                            ->label('صورة الإجابة')
                                            ->image()
                                            ->imageEditor()
                                            ->directory('answer_images')
                                            ->disk('public')
                                            ->columnSpan(2)
                                            ->visible(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'صوري')
                                            ->required(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'صوري'),

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
                                    ->addable(fn(Forms\Get $get) => QuestionType::find($get('../question_type_id'))?->name != 'مقارنة')
                                    ->deletable(fn(Forms\Get $get) => QuestionType::find($get('../question_type_id'))?->name != 'مقارنة')
                                    ->columns(3),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exporter(QuestionExporter::class),
                ImportAction::make()->importer(QuestionImporter::class),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('رقم السؤال')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type.name')
                    ->label('نوع السؤال')
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
                    ->formatStateUsing(fn($state) => $state ? '<img src="' . $state . '" style="max-height: 50px; max-width: 50px;">' : 'No Image')
                    ->html()
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
            ->filters([
                Tables\Filters\SelectFilter::make('question_type_id')
                    ->label('نوع السؤال')
                    ->relationship('type', 'name'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('تم الانشاء من'),
                        Forms\Components\DatePicker::make('created_until')->label('تم الانشاء إلى'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview')
                        ->label('معاينة')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalContent(fn($record) => view('filament.resources.question-resource.preview', ['record' => $record]))
                        ->modalWidth('5xl')
                        ->slideOver()
                        ->modalFooterActions(fn() => []),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make()
                        ->label('تعديل'),
                    Tables\Actions\DeleteAction::make()
                        ->label('حذف'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('السؤال')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('type.name')
                                    ->label('نوع السؤال'),
                                TextEntry::make('id')
                                    ->label('رقم السؤال'),
                                TextEntry::make('created_at')
                                    ->label('تاريخ الاضافة')
                                    ->dateTime(),
                            ]),
                        TextEntry::make('text')
                            ->label('نص السؤال')
                            ->columnSpanFull()
                            ->prose(),
                        ImageEntry::make('image_path')
                            ->label('صورة مرفقة')
                            ->disk('public')
                            ->columnSpanFull(),
                    ]),

                Section::make('الشرح')
                    ->schema([
                        TextEntry::make('explanation_text')
                            ->label('شرح السؤال')
                            ->columnSpanFull()
                            ->prose(),
                        ImageEntry::make('explanation_text_image_path')
                            ->label('صورة مرفقة للشرح')
                            ->disk('public')
                            ->columnSpanFull(),
                        TextEntry::make('explanation_video_url')
                            ->label('فيديو شرح السؤال')
                            ->url(fn($state) => $state)
                            ->openUrlInNewTab()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('الإجابات')
                    ->schema([
                        RepeatableEntry::make('answers')
                            ->label('قائمة الإجابات')
                            ->schema([
                                TextEntry::make('text')
                                    ->label('نص الإجابة')
                                    ->visible(fn($record) => !empty($record->text)),
                                ImageEntry::make('image_path')
                                    ->label('صورة الإجابة')
                                    ->disk('public')
                                    ->visible(fn($record) => !empty($record->image_path)),
                                IconEntry::make('is_correct')
                                    ->label('صحيحة؟')
                                    ->boolean(),
                            ])
                            ->columns(3),
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
            'view' => Pages\ViewQuestion::route('/{record}'),
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

    public static function getHeaderWidgets(): array
    {
        return [
            QuestionStatsOverview::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل'),

            'text' => Tab::make('نصي')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('type', fn($q) => $q->where('name', 'نصي'))),
            'image' => Tab::make('صوري')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('type', fn($q) => $q->where('name', 'صوري'))),
            'trending' => Tab::make('Trending')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('is_new', true)->whereNull('practice_exam_id')),
            'linked_to_model' => Tab::make('مرتبط بنموذج')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereNotNull('practice_exam_id')),
            'comparison' => Tab::make('مقارنة')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereHas('type', fn($q) => $q->where('name', 'مقارنة'))),
        ];
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
