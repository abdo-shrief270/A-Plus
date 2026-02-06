<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Move fields from users to students
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_league_id']);
            $table->dropColumn(['current_score', 'current_league_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->integer('current_score')->default(0);
            $table->foreignId('current_league_id')->nullable()->constrained('leagues')->nullOnDelete();
        });

        // 2. Refactor user_scores to student_scores
        Schema::rename('user_scores', 'student_scores');
        Schema::table('student_scores', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreignId('student_id')->after('id')->constrained('students')->cascadeOnDelete();
        });

        // 3. Refactor transactions/wallets to student (assuming wallet belongs to student)
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreignId('student_id')->after('id')->constrained('students')->cascadeOnDelete();
        });

        // 4. Refactor subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->foreignId('student_id')->after('id')->constrained('students')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Revert logic (simplified for development, might not fully restore data if migration runs in prod)
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['current_league_id']);
            $table->dropColumn(['current_score', 'current_league_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('current_score')->default(0);
            $table->foreignId('current_league_id')->nullable()->constrained('leagues')->nullOnDelete();
        });

        Schema::table('student_scores', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });
        Schema::rename('student_scores', 'user_scores');

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });
    }
};
