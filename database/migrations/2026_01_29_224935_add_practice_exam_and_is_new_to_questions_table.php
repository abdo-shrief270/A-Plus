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
        Schema::table('questions', function (Blueprint $table) {
            if (!Schema::hasColumn('questions', 'practice_exam_id')) {
                $table->foreignId('practice_exam_id')->nullable()->constrained('practice_exams')->nullOnDelete();
            }
            if (!Schema::hasColumn('questions', 'is_new')) {
                $table->boolean('is_new')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            if (Schema::hasColumn('questions', 'practice_exam_id')) {
                $table->dropForeign(['practice_exam_id']);
                $table->dropColumn('practice_exam_id');
            }
            if (Schema::hasColumn('questions', 'is_new')) {
                $table->dropColumn('is_new');
            }
        });
    }
};
