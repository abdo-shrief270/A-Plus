<?php

namespace App\Http\Resources\v2;

use App\Models\QuizSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Full session payload: summary + the question list (reveal-gated per row).
 * Expects questions.question.answers + questions.question.type eager-loaded.
 */
class QuizSessionDetailResource extends QuizSessionResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['questions'] = QuizQuestionResource::collectionFor($this->resource, $this->questions);

        // Per-section performance — only meaningful (and only computed) for a
        // finalized simulation, where section coverage is the whole point.
        if ($this->is_simulation && $this->isFinalized()) {
            $data['section_breakdown'] = $this->sectionBreakdown();
        }

        return $data;
    }

    /**
     * Correct/total per section for this session's questions. Each question is
     * attributed to one section (its first category's section) so totals sum to
     * the question count. One grouped query.
     *
     * @return array<int, array{section_id: int, name: string, total: int, correct: int, accuracy: float}>
     */
    protected function sectionBreakdown(): array
    {
        $query = DB::table('quiz_session_questions as qsq')
            ->join('category_questions as cq', 'cq.question_id', '=', 'qsq.question_id')
            ->join('section_categories as sc', 'sc.id', '=', 'cq.section_category_id')
            ->join('exam_sections as es', 'es.id', '=', 'sc.exam_section_id')
            ->where('qsq.quiz_session_id', $this->id);

        // Questions can be linked to categories in OTHER exams too — restrict to
        // this session's own sections so the breakdown reflects the actual exam.
        $sectionIds = $this->section_ids ?? [];
        if (!empty($sectionIds)) {
            $query->whereIn('es.id', $sectionIds);
        } else {
            $query->where('es.exam_id', $this->student->exam_id ?? 0);
        }

        $rows = $query->orderBy('es.id')
            ->get(['qsq.question_id', 'qsq.is_correct', 'es.id as section_id', 'es.name as section_name']);

        // Attribute each question to a single section (first seen) to avoid
        // double-counting questions linked to multiple categories.
        $seen = [];
        $bySection = [];
        foreach ($rows as $row) {
            if (isset($seen[$row->question_id])) {
                continue;
            }
            $seen[$row->question_id] = true;
            $sid = $row->section_id;
            $bySection[$sid] ??= ['section_id' => $sid, 'name' => $row->section_name, 'total' => 0, 'correct' => 0];
            $bySection[$sid]['total']++;
            if ($row->is_correct) {
                $bySection[$sid]['correct']++;
            }
        }

        return array_values(array_map(function ($s) {
            $s['accuracy'] = $s['total'] > 0 ? round($s['correct'] / $s['total'] * 100, 1) : 0.0;

            return $s;
        }, $bySection));
    }
}
