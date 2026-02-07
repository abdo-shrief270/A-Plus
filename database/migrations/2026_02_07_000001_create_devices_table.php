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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_id', 255)->comment('Unique device identifier from client');
            $table->string('name')->nullable()->comment('User-friendly device name');
            $table->string('platform')->nullable()->comment('iOS, Android, Web, etc.');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_trusted')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'device_id']);
            $table->index('device_id');
        });

        // Add OTP columns to password_reset_tokens if not exists
        if (!Schema::hasColumn('password_reset_tokens', 'otp')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->string('otp', 6)->nullable()->after('token');
                $table->timestamp('otp_expires_at')->nullable()->after('otp');
                $table->enum('method', ['email', 'phone', 'whatsapp'])->nullable()->after('otp_expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');

        if (Schema::hasColumn('password_reset_tokens', 'otp')) {
            Schema::table('password_reset_tokens', function (Blueprint $table) {
                $table->dropColumn(['otp', 'otp_expires_at', 'method']);
            });
        }
    }
};
