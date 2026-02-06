<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->integer('points_cost')->default(0);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->integer('points_cost')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('points_cost');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('points_cost');
        });
    }
};
