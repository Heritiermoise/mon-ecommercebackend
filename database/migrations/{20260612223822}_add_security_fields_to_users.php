<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('utilisateurs', 'two_factor_enabled')) {
            Schema::table('utilisateurs', function (Blueprint $table) {
                $table->boolean('two_factor_enabled')->default(false);
                $table->string('two_factor_secret')->nullable();
                $table->timestamp('two_factor_last_verified')->nullable();
                $table->string('current_session_token')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip', 45)->nullable();
            });
        }

        if (!Schema::hasTable('user_sessions')) {
            Schema::create('user_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->string('session_token', 128)->unique();
                $table->string('ip_address', 45);
                $table->string('user_agent')->nullable();
                $table->timestamp('last_activity');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index(['user_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
        
        if (Schema::hasColumn('utilisateurs', 'two_factor_enabled')) {
            Schema::table('utilisateurs', function (Blueprint $table) {
                $table->dropColumn([
                    'two_factor_enabled',
                    'two_factor_secret',
                    'two_factor_last_verified',
                    'current_session_token',
                    'last_login_at',
                    'last_login_ip'
                ]);
            });
        }
    }
};