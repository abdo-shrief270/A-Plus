<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            // Set only for daily-challenge sessions; one challenge per student
            // per day (MySQL unique indexes ignore NULL rows).
            $table->date('challenge_date')->nullable()->after('status');
            $table->unique(['student_id', 'challenge_date']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_sessions', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'challenge_date']);
            $table->dropColumn('challenge_date');
        });
    }
};
