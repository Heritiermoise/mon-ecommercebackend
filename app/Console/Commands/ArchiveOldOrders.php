<?php

namespace App\Console\Commands;

use App\Models\Commande;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveOldOrders extends Command
{
    protected $signature = 'orders:archive-old {--months=24 : Age minimum en mois}';
    protected $description = 'Marquer les anciennes commandes livrees ou annulees comme archivees';

    public function handle()
    {
        $months = (int) $this->option('months');
        $limitDate = now()->subMonths($months);

        $count = Commande::where('created_at', '<', $limitDate)
            ->whereIn('statut', ['livree', 'annulee'])
            ->update([
                'note_admin' => DB::raw("CONCAT(COALESCE(note_admin, ''), ' [ARCHIVE]')"),
            ]);

        $this->info($count . ' commande(s) archivee(s)');

        return 0;
    }
}
