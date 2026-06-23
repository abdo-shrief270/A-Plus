<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Question;
use App\Models\SectionCategory;
use App\Models\Student;

/**
 * Computes the fixed free sample a trial student may access: the first N
 * study-plan lessons (by order) and the first category of the exam. Paid
 * students and guests are never gated (canAccess* returns true).
 */
class TrialEntitlementService
{
    public const LOCKED_REASON = 'trial_locked';
    public const LOCKED_MESSAGE = 'هذا المحتوى متاح في الخطة المدفوعة فقط. اشترك للوصول الكامل.';

    /** @return array<int,int> */
    public function allowedLessonIds(Student $student): array
    {
        return Lesson::where('exam_id', $student->exam_id)
            ->active()
            ->ordered()
            ->limit((int) config('learning.trial_lesson_count', 3))
            ->pluck('id')
            ->all();
    }

    public function allowedCategoryId(Student $student): ?int
    {
        return SectionCategory::whereHas('section', fn ($q) => $q->where('exam_id', $student->exam_id))
            ->orderBy('exam_section_id')
            ->orderBy('id')
            ->value('id');
    }

    public function canAccessLesson(Student $student, Lesson $lesson): bool
    {
        if (!$student->onTrial()) {
            return true;
        }

        return in_array($lesson->id, $this->allowedLessonIds($student), true);
    }

    public function canAccessCategory(Student $student, int $categoryId): bool
    {
        if (!$student->onTrial()) {
            return true;
        }

        return $categoryId === $this->allowedCategoryId($student);
    }

    public function canAccessQuestion(Student $student, Question $question): bool
    {
        if (!$student->onTrial()) {
            return true;
        }

        $allowed = $this->allowedCategoryId($student);
        if ($allowed === null) {
            return false;
        }

        return $question->categories()->where('section_categories.id', $allowed)->exists();
    }
}
