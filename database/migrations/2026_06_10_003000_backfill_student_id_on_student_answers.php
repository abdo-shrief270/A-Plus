<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Historically AnswerController saved answers with user_id only, leaving
     * student_id null — which hid every answer from the revision page (it reads
     * by student_id). Backfill student_id from the students table.
     */
    public function up(): void
    {
        DB::table('student_answers')
            ->whereNull('student_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                $userIds = $rows->pluck('user_id')->unique()->filter();
                $studentByUser = DB::table('students')
                    ->whereIn('user_id', $userIds)
                    ->pluck('id', 'user_id');

                foreach ($rows as $row) {
                    $studentId = $studentByUser[$row->user_id] ?? null;
                    if ($studentId) {
                        DB::table('student_answers')->where('id', $row->id)->update(['student_id' => $studentId]);
                    }
                }
            });
    }

    public function down(): void
    {
        // No-op: backfilled data is correct; we don't re-null it.
    }
};
