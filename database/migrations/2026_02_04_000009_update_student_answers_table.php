<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->change();
            $table->foreignId('answer_id')->nullable()->change();
            $table->json('user_answer')->nullable()->after('answer_id'); // Store text or complex answer
            $table->decimal('score_earned', 8, 2)->default(0)->after('is_correct');
        });
    }

    public function down(): void
    {
        Schema::table('student_answers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'user_answer', 'score_earned']);
            $table->foreignId('answer_id')->nullable(false)->change();
            $table->foreignId('student_id')->nullable(false)->change();
        });
    }
};
