<?php

namespace App\Console\Commands;

use App\Services\StockService;
use Illuminate\Console\Command;

class StockAlertReport extends Command
{
    protected $signature = 'stock:alert-report {--seuil=10 : Seuil de stock faible}';
    protected $description = 'Generer un rapport des produits en stock faible';

    public function handle()
    {
        $seuil = (int) $this->option('seuil');
        $stats = StockService::getStatistiques();

        $this->info('Produits totaux: ' . $stats['total_produits']);
        $this->info('En stock: ' . $stats['en_stock']);
        $this->info('En rupture: ' . $stats['en_rupture']);
        $this->info('Stock faible: ' . $stats['stock_faible']);
        $this->info('Seuil surveille: ' . $seuil);

        return 0;
    }
}
