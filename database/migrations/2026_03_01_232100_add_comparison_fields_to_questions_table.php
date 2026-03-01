<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->text('comparison_value_1')->nullable()->after('text');
            $table->string('comparison_image_1')->nullable()->after('comparison_value_1');
            $table->text('comparison_value_2')->nullable()->after('comparison_image_1');
            $table->string('comparison_image_2')->nullable()->after('comparison_value_2');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'comparison_value_1',
                'comparison_image_1',
                'comparison_value_2',
                'comparison_image_2',
            ]);
        });
    }
};
