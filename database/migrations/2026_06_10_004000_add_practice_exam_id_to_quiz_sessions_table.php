<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            // Set when the session is a run of a specific practice-exam model
            // (نموذج). Null for regular quizzes / daily / simulation.
            $table->foreignId('practice_exam_id')->nullable()->after('is_simulation')
                ->constrained('practice_exams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('practice_exam_id');
        });
    }
};
