<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE utilisateurs MODIFY last_login_ip TEXT NULL');
            DB::statement('ALTER TABLE utilisateurs MODIFY nom TEXT');
            DB::statement('ALTER TABLE utilisateurs MODIFY telephone TEXT NULL');
            DB::statement('ALTER TABLE utilisateurs MODIFY two_factor_secret TEXT NULL');
            DB::statement('ALTER TABLE utilisateurs MODIFY current_session_token TEXT NULL');
        } catch (\Exception $e) {
            // Ignorer si déjà fait
        }
    }

    public function down(): void
    {
    }
};