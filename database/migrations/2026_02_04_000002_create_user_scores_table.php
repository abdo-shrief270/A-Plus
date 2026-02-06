<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('score');
            $table->string('reason')->index(); // e.g. lesson_complete, question_correct
            $table->nullableMorphs('reference'); // e.g. Lesson:1, Question:5
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scores');
    }
};
