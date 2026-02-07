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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('logo')->nullable(); // Path to logo image
            $table->string('color', 7)->default('#10B981'); // Hex color code
            $table->integer('order')->default(0); // Lesson order within exam
            $table->integer('duration_minutes')->default(30); // Expected duration
            $table->boolean('is_active')->default(true);
            $table->integer('points_cost')->default(0);
            $table->timestamps();

            $table->index(['exam_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
