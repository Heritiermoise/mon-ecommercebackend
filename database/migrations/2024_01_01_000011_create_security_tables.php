<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_blocked_ips')) {
            Schema::dropIfExists('security_blocked_ips');
        }

        Schema::create('security_blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason', 255);
            $table->timestamp('blocked_at')->useCurrent();
            $table->timestamp('blocked_until')->nullable();
            $table->integer('attempts')->default(1);
            $table->timestamps();
        });

        if (Schema::hasTable('security_logs')) {
            Schema::dropIfExists('security_logs');
        }

        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('path', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('event_type', 50)->default('access');
            $table->text('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ip_address', 'created_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('security_blocked_ips');
    }
};