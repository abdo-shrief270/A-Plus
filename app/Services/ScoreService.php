<?php

namespace App\Services;

use App\Models\League;
use App\Models\Student;
use App\Models\StudentScore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    /**
     * Add score to a student and check for league promotion.
     */
    public function addScore(Student $student, int $amount, string $reason, ?Model $reference = null): StudentScore
    {
        return DB::transaction(function () use ($student, $amount, $reason, $reference) {
            // 1. Create Score Record
            $score = StudentScore::create([
                'student_id' => $student->id,
                'score' => $amount,
                'reason' => $reason,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
            ]);

            // 2. Update Student Cached Score
            $student->increment('current_score', $amount);

            // 3. Check for League Promotion
            $this->checkLeaguePromotion($student);

            return $score;
        });
    }

    protected function checkLeaguePromotion(Student $student)
    {
        // Find the highest league where min_score <= student.current_score
        $targetLeague = League::where('min_score', '<=', $student->current_score)
            ->orderByDesc('min_score')
            ->first();

        if ($targetLeague && $targetLeague->id !== $student->current_league_id) {
            $student->update(['current_league_id' => $targetLeague->id]);
            // Dispatch event: StudentPromotedToLeague ($student, $targetLeague)
        }
    }
}
