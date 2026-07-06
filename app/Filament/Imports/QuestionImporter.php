<?php

namespace App\Filament\Imports;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\SectionCategory;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuestionImporter extends Importer
{
    protected static ?string $model = Question::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('text')
                ->label('نص السؤال')
                ->guess(['text', 'text question', 'question'])
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('answer_1')
                ->label('إجابة 1')
                ->guess(['answer_1'])
                ->requiredMapping()
                ->fillRecordUsing(fn () => null)
                ->rules(['required']),

            ImportColumn::make('answer_2')
                ->label('إجابة 2')
                ->guess(['answer_2'])
                ->requiredMapping()
                ->fillRecordUsing(fn () => null)
                ->rules(['required']),

            ImportColumn::make('answer_3')
                ->label('إجابة 3')
                ->guess(['answer_3'])
                ->requiredMapping()
                ->fillRecordUsing(fn () => null)
                ->rules(['required']),

            ImportColumn::make('answer_4')
                ->label('إجابة 4')
                ->guess(['answer_4'])
                ->requiredMapping()
                ->fillRecordUsing(fn () => null)
                ->rules(['required']),

            ImportColumn::make('correct_answer')
                ->label('الإجابة الصحيحة')
                ->guess(['correct_answer'])
                ->requiredMapping()
                ->fillRecordUsing(fn () => null)
                ->rules(['required']),

            ImportColumn::make('explanation_text')
                ->label('شرح السؤال')
                ->guess(['explanation_text', 'answer_explaination', 'answer_explanation', 'explanation']),

            // Comparison questions (مقارنة): two values to compare. When either
            // is present the row is saved as a comparison-type question.
            ImportColumn::make('comparison_value_1')
                ->label('قيمة المقارنة 1')
                ->guess(['comparison_value_1', 'comparison_1', 'value_1', 'القيمة الأولى']),

            ImportColumn::make('comparison_value_2')
                ->label('قيمة المقارنة 2')
                ->guess(['comparison_value_2', 'comparison_2', 'value_2', 'القيمة الثانية']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('question_type_id')
                ->label('نوع السؤال')
                ->options(fn () => QuestionType::query()->pluck('name', 'id'))
                ->default(fn () => QuestionType::query()->where('name', 'نصي')->value('id'))
                ->required(),

            Select::make('section_category_id')
                ->label('الفئة')
                ->searchable()
                ->options(function () {
                    return SectionCategory::with('section.exam')
                        ->get()
                        ->mapWithKeys(function ($cat) {
                            $label = ($cat->section?->exam?->name ?? '-') . ' > '
                                . ($cat->section?->name ?? '-') . ' > '
                                . $cat->name;
                            return [$cat->id => $label];
                        });
                })
                ->required(),
        ];
    }

    public function resolveRecord(): ?Question
    {
        return new Question([
            'uuid' => (string) Str::uuid(),
            'question_type_id' => $this->options['question_type_id'],
        ]);
    }

    /**
     * Runs after the columns are filled onto the record but before persisting.
     * Rows carrying comparison values become comparison-type questions.
     */
    protected function beforeSave(): void
    {
        // Normalize blanks to null so plain rows keep clean columns.
        $this->record->comparison_value_1 = filled($this->record->comparison_value_1)
            ? $this->record->comparison_value_1
            : null;
        $this->record->comparison_value_2 = filled($this->record->comparison_value_2)
            ? $this->record->comparison_value_2
            : null;

        if ($this->record->comparison_value_1 !== null || $this->record->comparison_value_2 !== null) {
            $typeId = self::comparisonTypeId();
            if ($typeId !== null) {
                $this->record->question_type_id = $typeId;
            } else {
                Log::warning('[question_import] comparison values present but no "مقارنة" question type exists; keeping selected type', [
                    'uuid' => $this->record->uuid,
                ]);
            }
        }
    }

    protected static ?int $comparisonTypeId = null;

    protected static function comparisonTypeId(): ?int
    {
        return self::$comparisonTypeId ??= QuestionType::where('name', 'مقارنة')->value('id');
    }

    protected function afterSave(): void
    {
        $correct = trim((string) ($this->data['correct_answer'] ?? ''));
        $answers = [];
        $hasCorrect = false;

        foreach ([1, 2, 3, 4] as $order) {
            $text = trim((string) ($this->data["answer_{$order}"] ?? ''));
            if ($text === '') {
                continue;
            }

            $isCorrect = $text === $correct;
            if ($isCorrect) {
                $hasCorrect = true;
            }

            $this->record->answers()->create([
                'text' => $text,
                'is_correct' => $isCorrect,
                'order' => $order,
            ]);

            $answers[$order] = $text;
        }

        if (! empty($this->options['section_category_id'])) {
            $this->record->categories()->syncWithoutDetaching([$this->options['section_category_id']]);
        }

        if (! $hasCorrect) {
            Log::warning('[question_import_needs_review] correct_answer did not match any option', [
                'question_id' => $this->record->id,
                'uuid' => $this->record->uuid,
                'correct_answer' => $correct,
                'answers' => $answers,
            ]);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your question import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed validation and were not imported.';
        }

        $needsReview = Question::whereDoesntHave('answers', fn ($q) => $q->where('is_correct', true))->count();
        if ($needsReview > 0) {
            $body .= ' ' . number_format($needsReview) . ' imported question(s) need review (no matching correct_answer). Open the "يحتاج مراجعة" tab on the Questions page to find them.';
        }

        return $body;
    }
}
