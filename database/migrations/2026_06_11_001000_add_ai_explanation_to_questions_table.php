<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // Cached AI-generated explanation — generated once per question and
            // reused for everyone, so OpenAI is called at most once per question.
            $table->text('ai_explanation')->nullable()->after('explanation_video_url');
            $table->timestamp('ai_explanation_generated_at')->nullable()->after('ai_explanation');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['ai_explanation', 'ai_explanation_generated_at']);
        });
    }
};
