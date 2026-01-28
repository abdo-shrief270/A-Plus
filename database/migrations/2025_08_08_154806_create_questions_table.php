<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->uuid();
            $table->text('text');
            $table->foreignId('question_type_id')->constrained('question_types','id')->cascadeOnDelete();
            $table->text('image_path')->nullable();
            $table->text('explanation_text')->nullable();
            $table->text('explanation_text_image_path')->nullable();
            $table->text('explanation_video_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
