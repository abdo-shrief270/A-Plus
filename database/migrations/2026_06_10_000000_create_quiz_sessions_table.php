<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students', 'id')->cascadeOnDelete();
            $table->enum('mode', ['tutor', 'exam']);
            $table->enum('source', ['random', 'unanswered', 'wrong', 'bookmarked']);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->nullable();
            $table->json('section_ids')->nullable();
            $table->json('category_ids')->nullable();
            $table->unsignedSmallInteger('question_count');
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->enum('status', ['in_progress', 'completed', 'expired', 'abandoned'])->default('in_progress');
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('incorrect_count')->default(0);
            $table->unsignedSmallInteger('skipped_count')->default(0);
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('deadline_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sessions');
    }
};
