<?php

namespace App\Filament\Imports;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\SectionCategory;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
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
            Log::warning('Question import: correct_answer did not match any option', [
                'question_id' => $this->record->id,
                'uuid' => $this->record->uuid,
                'correct_answer' => $correct,
                'answers' => $answers,
            ]);

            throw new RowImportFailedException(
                "Question #{$this->record->id} imported but correct_answer did not match any of answer_1..answer_4. Review the question and mark the correct answer manually."
            );
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your question import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' were imported but flagged for review (no matching correct_answer). The failed-rows CSV contains each flagged question id.';
        }

        return $body;
    }
}
