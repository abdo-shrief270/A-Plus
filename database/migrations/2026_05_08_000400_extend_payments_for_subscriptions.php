<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('enrollment_id')
                ->constrained('subscriptions')
                ->nullOnDelete();
            $table->json('metadata')->nullable()->after('payload');
            $table->string('description')->nullable()->after('amount');
            $table->timestamp('paid_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn(['metadata', 'description', 'paid_at']);
        });
    }
};
