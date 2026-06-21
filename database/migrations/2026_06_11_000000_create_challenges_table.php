<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_student_id')->constrained('students', 'id')->cascadeOnDelete();
            $table->string('invite_code', 12)->unique();
            $table->json('question_ids');             // frozen set everyone answers
            $table->unsignedSmallInteger('question_count');
            $table->unsignedInteger('time_limit_seconds')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });

        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->foreignId('challenge_id')->nullable()->after('practice_exam_id')
                ->constrained('challenges')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('challenge_id');
        });
        Schema::dropIfExists('challenges');
    }
};
