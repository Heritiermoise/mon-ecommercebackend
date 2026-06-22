<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SecurityService;

class SecurityCleanupLogs extends Command
{
    protected $signature = 'security:cleanup-logs {--days=30 : Nombre de jours a conserver}';
    protected $description = 'Nettoyer les anciens logs de securite';

    public function handle()
    {
        $days = $this->option('days');
        $this->info("Nettoyage des logs de plus de $days jours...");

        $deleted = SecurityService::cleanupOldLogs($days);

        $this->info("$deleted logs supprimes");
        
        return 0;
    }
}