<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Article;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\SectionCategory;
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
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('التصنيف')
                        ->icon('heroicon-o-tag')
                        ->description('اختر الفئة أو القطعة')
                        ->schema([
                            Forms\Components\Radio::make('assign_to')
                                ->label('ربط السؤال بـ')
                                ->options([
                                    'category' => 'فئة (تصنيف فرعي)',
                                    'article' => 'قطعة',
                                ])
                                ->default('category')
                                ->required()
                                ->live()
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Forms\Components\Radio $component, Forms\Get $get, ?Question $record) {
                                    if ($record) {
                                        $hasArticle = $record->articles()->exists();
                                        $component->state($hasArticle ? 'article' : 'category');
                                    }
                                }),

                            Forms\Components\Select::make('category_ids')
                                ->label('الفئة')
                                ->searchable()
                                ->multiple()
                                ->required(fn(Forms\Get $get) => $get('assign_to') === 'category')
                                ->visible(fn(Forms\Get $get) => $get('assign_to') === 'category')
                                ->options(function () {
                                    return SectionCategory::with('section.exam')
                                        ->get()
                                        ->mapWithKeys(function ($cat) {
                                            $label = ($cat->section?->exam?->name ?? '') . ' > ' . ($cat->section?->name ?? '') . ' > ' . $cat->name;
                                            return [$cat->id => $label];
                                        });
                                })
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Forms\Components\Select $component, ?Question $record) {
                                    if ($record) {
                                        $component->state($record->categories()->pluck('section_categories.id')->toArray());
                                    }
                                }),

                            Forms\Components\Select::make('article_ids')
                                ->label('القطعة')
                                ->searchable()
                                ->multiple()
                                ->required(fn(Forms\Get $get) => $get('assign_to') === 'article')
                                ->visible(fn(Forms\Get $get) => $get('assign_to') === 'article')
                                ->options(function () {
                                    return Article::with('category.section.exam')
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(function ($article) {
                                            $label = ($article->category?->section?->exam?->name ?? '') . ' > '
                                                . ($article->category?->section?->name ?? '') . ' > '
                                                . ($article->category?->name ?? '') . ' > '
                                                . $article->title;
                                            return [$article->id => $label];
                                        });
                                })
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Forms\Components\Select $component, ?Question $record) {
                                    if ($record) {
                                        $component->state($record->articles()->pluck('articles.id')->toArray());
                                    }
                                }),
                        ]),

                    Forms\Components\Wizard\Step::make('السؤال')
                        ->icon('heroicon-o-question-mark-circle')
                        ->description('بيانات السؤال الأساسية')
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
                            Forms\Components\ViewField::make('equation_quick_access_question')
                                ->view('filament.forms.equation-quick-access')
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            Forms\Components\RichEditor::make('text')
                                ->label('نص السؤال')
                                ->required()
                                ->live(onBlur: true)
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('question_text_images')
                                ->fileAttachmentsVisibility('public')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'strike',
                                    'link',
                                    'orderedList',
                                    'bulletList',
                                    'attachFiles',
                                    'redo',
                                    'undo',
                                ])
                                ->helperText('للكسور والمعادلات استخدم صيغة LaTeX مثل: $\frac{1}{2}$ أو $$\frac{1 + \frac{1}{2}}{\frac{1}{4}}$$')
                                ->columnSpanFull(),
                            Forms\Components\ViewField::make('text_preview')
                                ->view('filament.forms.math-preview')
                                ->statePath('text')
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            // Comparison values — only for مقارنة type
                            Forms\Components\Section::make('القيم المقارنة')
                                ->description('أدخل القيمتين اللتين سيتم المقارنة بينهما')
                                ->visible(fn(Forms\Get $get) => QuestionType::find($get('question_type_id'))?->name == 'مقارنة')
                                ->schema([
                                    Forms\Components\Fieldset::make('القيمة الأولى')
                                        ->schema([
                                            Forms\Components\RichEditor::make('comparison_value_1')
                                                ->label('نص القيمة الأولى')
                                                ->live(onBlur: true)
                                                ->fileAttachmentsDisk('public')
                                                ->fileAttachmentsDirectory('comparison_text_images')
                                                ->fileAttachmentsVisibility('public')
                                                ->toolbarButtons(['bold', 'italic', 'attachFiles', 'undo', 'redo'])
                                                ->helperText('للكسور: $\frac{بسط}{مقام}$'),
                                            Forms\Components\ViewField::make('comparison_value_1_preview')
                                                ->view('filament.forms.math-preview')
                                                ->statePath('comparison_value_1')
                                                ->dehydrated(false),
                                            Forms\Components\FileUpload::make('comparison_image_1')
                                                ->label('صورة القيمة الأولى')
                                                ->image()
                                                ->imageEditor()
                                                ->directory('comparison_images')
                                                ->disk('public')
                                                ->imageEditorEmptyFillColor('#000000')
                                                ->moveFiles(),
                                        ])->columns(2),
                                    Forms\Components\Fieldset::make('القيمة الثانية')
                                        ->schema([
                                            Forms\Components\RichEditor::make('comparison_value_2')
                                                ->label('نص القيمة الثانية')
                                                ->live(onBlur: true)
                                                ->fileAttachmentsDisk('public')
                                                ->fileAttachmentsDirectory('comparison_text_images')
                                                ->fileAttachmentsVisibility('public')
                                                ->toolbarButtons(['bold', 'italic', 'attachFiles', 'undo', 'redo'])
                                                ->helperText('للكسور: $\frac{بسط}{مقام}$'),
                                            Forms\Components\ViewField::make('comparison_value_2_preview')
                                                ->view('filament.forms.math-preview')
                                                ->statePath('comparison_value_2')
                                                ->dehydrated(false),
                                            Forms\Components\FileUpload::make('comparison_image_2')
                                                ->label('صورة القيمة الثانية')
                                                ->image()
                                                ->imageEditor()
                                                ->directory('comparison_images')
                                                ->disk('public')
                                                ->imageEditorEmptyFillColor('#000000')
                                                ->moveFiles(),
                                        ])->columns(2),
                                ])
                                ->columns(1),

                            Forms\Components\Toggle::make('is_new')
                                ->label('سؤال جديد (Trending)')
                                ->default(false),
                            Forms\Components\Select::make('practice_exam_id')
                                ->label('النموذج')
                                ->relationship('practiceExam', 'title')
                                ->searchable()
                                ->preload(),
                        ]),

                    Forms\Components\Wizard\Step::make('الشرح')
                        ->icon('heroicon-o-light-bulb')
                        ->description('شرح السؤال والتوضيح')
                        ->schema([
                            Forms\Components\ViewField::make('equation_quick_access_explanation')
                                ->view('filament.forms.equation-quick-access')
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            Forms\Components\MarkdownEditor::make('explanation_text')
                                ->label('شرح السؤال')
                                ->live(onBlur: true)
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('explanation_text_images')
                                ->fileAttachmentsVisibility('public')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'strike',
                                    'link',
                                    'orderedList',
                                    'bulletList',
                                    'attachFiles',
                                    'redo',
                                    'undo',
                                ])
                                ->helperText('للكسور والمعادلات استخدم صيغة LaTeX مثل: $\frac{1}{2}$')
                                ->columnSpanFull(),
                            Forms\Components\ViewField::make('explanation_text_preview')
                                ->view('filament.forms.markdown-math-preview')
                                ->statePath('explanation_text')
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('explanation_video_url')
                                ->label('فيديو شرح السؤال')
                                ->url()
                                ->activeUrl(),
                        ]),

                    Forms\Components\Wizard\Step::make('الإجابات')
                        ->icon('heroicon-o-check-circle')
                        ->description('إجابات السؤال')
                        ->schema([
                            Forms\Components\ViewField::make('equation_quick_access_answers')
                                ->view('filament.forms.equation-quick-access')
                                ->dehydrated(false)
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('answers')
                                ->label('الإجابات')
                                ->relationship('answers')
                                ->orderColumn('order')
                                ->schema([
                                    Forms\Components\RichEditor::make('text')
                                        ->label('نص الإجابة')
                                        ->columnSpan(2)
                                        ->live(onBlur: true)
                                        ->fileAttachmentsDisk('public')
                                        ->fileAttachmentsDirectory('answer_text_images')
                                        ->fileAttachmentsVisibility('public')
                                        ->toolbarButtons([
                                            'bold',
                                            'italic',
                                            'attachFiles',
                                            'undo',
                                            'redo',
                                        ])
                                        ->helperText('للكسور: $\frac{بسط}{مقام}$')
                                        ->visible(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'نصي' || QuestionType::find($get('../../question_type_id'))?->name == 'مقارنة')
                                        ->disabled(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'مقارنة')
                                        ->required(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'نصي'),
                                    Forms\Components\ViewField::make('text_math_preview')
                                        ->view('filament.forms.answer-math-preview')
                                        ->statePath('text')
                                        ->dehydrated(false)
                                        ->columnSpan(2)
                                        ->visible(fn(Forms\Get $get) => QuestionType::find($get('../../question_type_id'))?->name == 'نصي' || QuestionType::find($get('../../question_type_id'))?->name == 'مقارنة'),

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
                ])
                ->skippable(fn (string $operation) => $operation === 'edit')
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exporter(QuestionExporter::class),
                ImportAction::make()->importer(QuestionImporter::class),
                Tables\Actions\Action::make('uploadQuestionImages')
                    ->label('رفع صور الأسئلة (ZIP)')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->modalHeading('رفع صور الأسئلة عبر ZIP')
                    ->modalDescription('ارفع ملف ZIP يحتوي على صور الأسئلة. سيتم استخراج جميع الصور (تجاهل المجلدات) ومطابقة الأسماء مع ![](اسم الصورة).')
                    ->form([
                        Forms\Components\FileUpload::make('zip')
                            ->label('ملف ZIP')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
                            ->disk('local')
                            ->directory('temp/question_image_zips')
                            ->previewable(false)
                            ->openable(false)
                            ->downloadable(false)
                            ->maxSize(512000)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $zipRelPath = is_array($data['zip']) ? ($data['zip'][0] ?? null) : $data['zip'];
                        if (! $zipRelPath) {
                            \Filament\Notifications\Notification::make()
                                ->title('لم يتم رفع أي ملف')
                                ->danger()->send();
                            return;
                        }

                        $zipAbsPath = \Illuminate\Support\Facades\Storage::disk('local')->path($zipRelPath);
                        if (! is_file($zipAbsPath)) {
                            \Filament\Notifications\Notification::make()
                                ->title('تعذر قراءة الملف')
                                ->danger()->send();
                            return;
                        }

                        $zip = new \ZipArchive();
                        if ($zip->open($zipAbsPath) !== true) {
                            @unlink($zipAbsPath);
                            \Filament\Notifications\Notification::make()
                                ->title('الملف ليس ZIP صالحاً')
                                ->danger()->send();
                            return;
                        }

                        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
                        $maxTotalBytes = 500 * 1024 * 1024;
                        $targetDir = storage_path('app/public/question_text_images');
                        if (! is_dir($targetDir)) {
                            @mkdir($targetDir, 0755, true);
                        }

                        $extractedBytes = 0;
                        $map = [];
                        $collisions = 0;
                        $skipped = 0;

                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $stat = $zip->statIndex($i);
                            if (! $stat) { continue; }

                            $entryName = $stat['name'];
                            if (str_ends_with($entryName, '/')) { continue; }

                            $basename = basename(str_replace('\\', '/', $entryName));
                            if ($basename === '' || str_starts_with($basename, '.')) { continue; }

                            $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                            if (! in_array($ext, $allowedExts, true)) { $skipped++; continue; }

                            $extractedBytes += (int) ($stat['size'] ?? 0);
                            if ($extractedBytes > $maxTotalBytes) { break; }

                            $stream = $zip->getStream($entryName);
                            if (! $stream) { $skipped++; continue; }
                            $contents = stream_get_contents($stream);
                            fclose($stream);
                            if ($contents === false) { $skipped++; continue; }

                            $destPath = $targetDir . DIRECTORY_SEPARATOR . $basename;
                            if (is_file($destPath)) { $collisions++; }
                            file_put_contents($destPath, $contents);
                            $map[$basename] = 'question_text_images/' . $basename;
                        }

                        $zip->close();
                        @unlink($zipAbsPath);

                        if (empty($map)) {
                            \Filament\Notifications\Notification::make()
                                ->title('لم يتم استخراج أي صورة من الملف')
                                ->warning()->send();
                            return;
                        }

                        $updatedQuestions = 0;
                        $replacedRefs = 0;
                        $unmatched = [];

                        Question::query()
                            ->where(function ($q) {
                                $q->where('text', 'like', '%![](%')
                                    ->orWhere('explanation_text', 'like', '%![](%');
                            })
                            ->chunkById(200, function ($chunk) use ($map, &$updatedQuestions, &$replacedRefs, &$unmatched) {
                                foreach ($chunk as $question) {
                                    $dirty = false;
                                    foreach (['text', 'explanation_text'] as $field) {
                                        $value = $question->getAttribute($field);
                                        if (! $value) { continue; }

                                        $new = preg_replace_callback(
                                            '/!\[\]\(([^)\s]+)\)/u',
                                            function ($m) use ($map, &$replacedRefs, &$unmatched) {
                                                $filename = basename($m[1]);
                                                if (isset($map[$filename])) {
                                                    $replacedRefs++;
                                                    return '![](' . \Illuminate\Support\Facades\Storage::url($map[$filename]) . ')';
                                                }
                                                $unmatched[$filename] = true;
                                                return $m[0];
                                            },
                                            $value
                                        );

                                        if ($new !== $value) {
                                            $question->setAttribute($field, $new);
                                            $dirty = true;
                                        }
                                    }
                                    if ($dirty) {
                                        $question->save();
                                        $updatedQuestions++;
                                    }
                                }
                            });

                        $body = 'تم استخراج ' . count($map) . ' صورة. ';
                        $body .= "تم تحديث {$updatedQuestions} سؤال، واستبدال {$replacedRefs} مرجع صورة.";
                        if ($skipped > 0) { $body .= " تم تخطي {$skipped} ملف غير مدعوم."; }
                        if ($collisions > 0) { $body .= " تم الكتابة فوق {$collisions} ملف بنفس الاسم."; }
                        if (! empty($unmatched)) { $body .= ' صور مشار إليها في النصوص لكنها غير موجودة بالملف: ' . count($unmatched) . '.'; }

                        \Filament\Notifications\Notification::make()
                            ->title('اكتمل رفع صور الأسئلة')
                            ->body($body)
                            ->success()->send();
                    }),
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
                    ->formatStateUsing(function (string $state): string {
                        $text = strip_tags($state);
                        $text = \Illuminate\Support\Str::limit($text, 100);
                        $escaped = e($text);
                        return '<span class="math-content" x-data x-init="
                            let el = $el;
                            function tryRender() {
                                if (typeof renderMathInElement !== \'undefined\') {
                                    renderMathInElement(el, {
                                        delimiters: [
                                            {left: \'$$\', right: \'$$\', display: true},
                                            {left: \'$\', right: \'$\', display: false},
                                        ],
                                        throwOnError: false
                                    });
                                } else {
                                    setTimeout(tryRender, 300);
                                }
                            }
                            tryRender();
                        ">' . $escaped . '</span>';
                    })
                    ->html()
                    ->wrap()
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
                    Tables\Actions\BulkAction::make('change_difficulty')
                        ->label('تغيير مستوى الصعوبة')
                        ->icon('heroicon-o-star')
                        ->form([
                            Forms\Components\Select::make('difficulty')
                                ->label('مستوى الصعوبة')
                                ->options([
                                    1 => '1 - سهل جداً',
                                    2 => '2 - سهل',
                                    3 => '3 - متوسط',
                                    4 => '4 - صعب',
                                    5 => '5 - صعب جداً',
                                ])
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['difficulty' => $data['difficulty']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_as_trending')
                        ->label('تغيير حالة (Trending)')
                        ->icon('heroicon-o-fire')
                        ->form([
                            Forms\Components\Toggle::make('is_new')
                                ->label('سؤال جديد؟')
                                ->default(true),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['is_new' => $data['is_new']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('assign_to_practice_exam')
                        ->label('تعيين إلى نموذج')
                        ->icon('heroicon-o-document-text')
                        ->form([
                            Forms\Components\Select::make('practice_exam_id')
                                ->label('النموذج')
                                ->options(function () {
                                    return \App\Models\PracticeExam::query()->pluck('title', 'id');
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update(['practice_exam_id' => $data['practice_exam_id']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('remove_from_practice_exam')
                        ->label('إزالة من النموذج')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                $record->update(['practice_exam_id' => null]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('assign_to_category')
                        ->label('تعيين إلى فئة')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Select::make('category_id')
                                ->label('الفئة (Category)')
                                ->options(function () {
                                    return \App\Models\SectionCategory::with(['section.exam'])
                                        ->get()
                                        ->mapWithKeys(function ($category) {
                                            $path = optional(optional($category->section)->exam)->name . ' > ' .
                                                optional($category->section)->name . ' > ' .
                                                $category->name;
                                            return [$category->id => $path];
                                        });
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            foreach ($records as $record) {
                                // Add category without removing existing ones
                                $record->categories()->syncWithoutDetaching([$data['category_id']]);
                                // Remove from articles since it's now in a category
                                $record->articles()->detach();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('assign_to_article')
                        ->label('تعيين إلى قطعة')
                        ->icon('heroicon-o-document-duplicate')
                        ->form([
                            Forms\Components\Select::make('article_id')
                                ->label('القطعة (Article)')
                                ->options(function () {
                                    return \App\Models\Article::with(['category.section.exam'])
                                        ->get()
                                        ->mapWithKeys(function ($article) {
                                            $path = optional(optional(optional($article->category)->section)->exam)->name . ' > ' .
                                                optional(optional($article->category)->section)->name . ' > ' .
                                                optional($article->category)->name . ' > ' .
                                                $article->title;
                                            return [$article->id => $path];
                                        });
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            foreach ($records as $record) {
                                // Add article without removing existing ones
                                $record->articles()->syncWithoutDetaching([$data['article_id']]);
                                // Remove from categories since it's now in an article
                                $record->categories()->detach();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

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
                            ->html()
                            ->prose()
                            ->formatStateUsing(fn ($state) => \App\Support\QuestionContentRenderer::render($state)),
                    ]),

                Section::make('القيم المقارنة')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('comparison_value_1')
                                    ->label('نص القيمة الأولى')
                                    ->html()
                                    ->prose()
                                    ->placeholder('-'),
                                TextEntry::make('comparison_value_2')
                                    ->label('نص القيمة الثانية')
                                    ->html()
                                    ->prose()
                                    ->placeholder('-'),
                                ImageEntry::make('comparison_image_1')
                                    ->label('صورة القيمة الأولى')
                                    ->disk('public'),
                                ImageEntry::make('comparison_image_2')
                                    ->label('صورة القيمة الثانية')
                                    ->disk('public'),
                            ]),
                    ])
                    ->visible(fn($record) => $record->type?->name === 'مقارنة')
                    ->collapsible(),

                Section::make('الشرح')
                    ->schema([
                        TextEntry::make('explanation_text')
                            ->label('شرح السؤال')
                            ->columnSpanFull()
                            ->html()
                            ->prose()
                            ->formatStateUsing(fn ($state) => \App\Support\QuestionContentRenderer::render($state)),
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
                                    ->html()
                                    ->prose()
                                    ->placeholder('-')
                                    ->formatStateUsing(fn ($state) => \App\Support\QuestionContentRenderer::render($state)),
                                ImageEntry::make('image_path')
                                    ->label('صورة الإجابة')
                                    ->disk('public')
                                    ->defaultImageUrl(null),
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
