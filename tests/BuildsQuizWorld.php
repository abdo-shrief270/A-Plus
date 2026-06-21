<?php

namespace Tests;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\Question;
use App\Models\QuestionType;
use App\Models\SectionCategory;
use App\Models\Student;
use App\Models\User;

/**
 * Helpers to assemble a minimal exam → section → category → questions world
 * for quiz tests, with deterministic correct answers.
 */
trait BuildsQuizWorld
{
    protected function makeStudent(?Exam $exam = null): Student
    {
        $exam ??= Exam::factory()->create();

        return Student::factory()->create([
            'user_id' => User::factory()->create(['type' => 'student'])->id,
            'exam_id' => $exam->id,
        ]);
    }

    /**
     * Create $count questions linked to $category, each with 4 answers where
     * exactly one (answer index $correctIndex, default 0) is correct.
     *
     * @return \Illuminate\Support\Collection<int, Question>
     */
    protected function makeQuestions(SectionCategory $category, int $count, int $correctIndex = 0)
    {
        $type = QuestionType::factory()->create();

        return collect(range(1, $count))->map(function () use ($category, $type, $correctIndex) {
            $question = Question::factory()->create(['question_type_id' => $type->id]);
            for ($i = 0; $i < 4; $i++) {
                Answer::factory()->create([
                    'question_id' => $question->id,
                    'is_correct' => $i === $correctIndex,
                    'order' => $i + 1,
                ]);
            }
            $category->questions()->attach($question->id);

            return $question->load('answers');
        });
    }

    protected function makeCategory(?Exam $exam = null, ?ExamSection $section = null): SectionCategory
    {
        $exam ??= Exam::factory()->create();
        $section ??= ExamSection::factory()->create(['exam_id' => $exam->id]);

        return SectionCategory::factory()->create(['exam_section_id' => $section->id]);
    }

    protected function correctAnswerId(Question $question): int
    {
        return $question->answers->firstWhere('is_correct', true)->id;
    }

    protected function wrongAnswerId(Question $question): int
    {
        return $question->answers->firstWhere('is_correct', false)->id;
    }
}
