<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Support\Facades\Log;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';
    protected $description = 'Annuler les commandes non payées après 5 jours et remettre le stock';

    public function handle()
    {
        $this->info('Vérification des commandes non payées...');

        $commandesExpirees = Commande::where('statut_paiement', 'non_paye')
            ->where('statut', 'en_attente')
            ->where('date_limite_paiement', '<', now())
            ->get();

        foreach ($commandesExpirees as $commande) {
            $this->info("Annulation de la commande {$commande->numero_commande}");

            // Annuler la commande
            $commande->update([
                'statut' => 'annulee',
                'note_admin' => 'Annulée automatiquement - Paiement non reçu dans les 5 jours',
            ]);

            Log::info("Commande {$commande->numero_commande} annulée automatiquement (paiement expiré)");
        }

        $this->info(count($commandesExpirees) . ' commande(s) annulée(s)');
    }
}