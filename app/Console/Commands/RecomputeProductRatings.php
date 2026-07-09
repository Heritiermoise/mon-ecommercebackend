<?php

namespace App\Console\Commands;

use App\Models\Avis;
use App\Models\Produit;
use Illuminate\Console\Command;

class RecomputeProductRatings extends Command
{
    protected $signature = 'products:recompute-ratings';
    protected $description = 'Recalculer les notes moyennes et le nombre d avis des produits';

    public function handle()
    {
        $count = 0;

        Produit::query()->select('id')->chunkById(100, function ($produits) use (&$count) {
            foreach ($produits as $produit) {
                $moyenne = Avis::where('produit_id', $produit->id)
                    ->where('est_approuve', true)
                    ->avg('note') ?? 0;

                $total = Avis::where('produit_id', $produit->id)
                    ->where('est_approuve', true)
                    ->count();

                Produit::where('id', $produit->id)->update([
                    'note_moyenne' => $moyenne,
                    'nombre_avis' => $total,
                ]);

                $count++;
            }
        });

        $this->info($count . ' produit(s) recalculé(s)');

        return 0;
    }
}
