<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupExpiredTokens extends Command
{
    protected $signature = 'sessions:cleanup-expired {--days=7 : Nombre de jours avant suppression}';
    protected $description = 'Supprimer les sessions et tokens expirés';

    public function handle()
    {
        $days = (int) $this->option('days');

        $deleted = DB::table('user_sessions')
            ->where('last_activity', '<', now()->subDays($days))
            ->delete();

        $this->info($deleted . ' session(s) supprimee(s)');

        return 0;
    }
}
