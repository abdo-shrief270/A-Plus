<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('current_score')->default(0);
            $table->foreignId('current_league_id')->nullable()->constrained('leagues')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_league_id']);
            $table->dropColumn(['current_score', 'current_league_id']);
        });
    }
};
