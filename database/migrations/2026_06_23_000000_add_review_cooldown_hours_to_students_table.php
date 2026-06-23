<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Per-student cooldown (in hours) before a wrong answer is due for
            // review again. Default 24 keeps the previous fixed behavior.
            $table->unsignedSmallInteger('review_cooldown_hours')->default(24)->after('id_number');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('review_cooldown_hours');
        });
    }
};
