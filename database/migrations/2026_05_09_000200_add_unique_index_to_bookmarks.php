<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop accidental duplicates first so the unique index can be added.
        // The multi-table DELETE syntax is MySQL-specific; on other drivers
        // (SQLite test DB) use a portable correlated subquery.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                DELETE b1 FROM bookmarks b1
                INNER JOIN bookmarks b2
                  ON b1.student_id = b2.student_id
                 AND b1.question_id = b2.question_id
                 AND b1.id > b2.id
            ');
        } else {
            DB::statement('
                DELETE FROM bookmarks
                WHERE id NOT IN (
                    SELECT MIN(id) FROM bookmarks GROUP BY student_id, question_id
                )
            ');
        }

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->unique(['student_id', 'question_id'], 'bookmarks_student_question_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropUnique('bookmarks_student_question_unique');
        });
    }
};
