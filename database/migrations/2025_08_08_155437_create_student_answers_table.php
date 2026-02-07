<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students', 'id')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions', 'id')->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained('answers', 'id')->cascadeOnDelete();
            $table->json('user_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('score_earned', 8, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};
