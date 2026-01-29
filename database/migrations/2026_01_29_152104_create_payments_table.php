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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // From Gateway
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Payer
            $table->foreignId('enrollment_id')->nullable()->constrained()->onDelete('set null'); // Can be null for bulk

            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('SAR');
            $table->string('payment_method'); // visa, mastercard, apple_pay...
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null');
            $table->json('payload')->nullable(); // Gateway response

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
