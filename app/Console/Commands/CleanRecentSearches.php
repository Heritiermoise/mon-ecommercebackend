<?php

namespace App\Console\Commands;

use App\Models\RechercheRecente;
use Illuminate\Console\Command;

class CleanRecentSearches extends Command
{
    protected $signature = 'searches:cleanup {--days=30 : Nombre de jours a conserver}';
    protected $description = 'Nettoyer les recherches recentes trop anciennes';

    public function handle()
    {
        $days = (int) $this->option('days');

        $count = RechercheRecente::where('created_at', '<', now()->subDays($days))->delete();

        $this->info($count . ' recherche(s) supprimee(s)');

        return 0;
    }
}
