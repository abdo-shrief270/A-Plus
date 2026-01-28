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
        Schema::create('lesson_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->integer('page_number')->default(1);
            $table->enum('type', ['text', 'image', 'question', 'mixed'])->default('text');
            $table->string('title');
            $table->json('content'); // Flexible JSON content based on type
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->index(['lesson_id', 'page_number']);
            $table->unique(['lesson_id', 'page_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_pages');
    }
};
