<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('category', ['inquiry', 'complaint', 'suggestion', 'technical', 'billing', 'other'])
                ->default('inquiry')
                ->after('status');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('priority', ['low', 'normal', 'high'])
                ->default('normal')
                ->after('status');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
