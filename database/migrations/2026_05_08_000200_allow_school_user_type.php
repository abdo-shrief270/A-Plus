<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Raw ENUM redefinition is MySQL-specific; SQLite (test DB) stores the
        // column as TEXT with no enum constraint, so this is a no-op there.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN type ENUM('parent','student','school') NOT NULL DEFAULT 'student'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN type ENUM('parent','student') NOT NULL DEFAULT 'student'");
    }
};
