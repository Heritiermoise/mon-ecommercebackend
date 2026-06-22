<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WishlistService;

class VerifierPrixWishlist extends Command
{
    protected $signature = 'wishlist:check-prices';
    protected $description = 'Verifie les baisses de prix pour les wishlists';

    public function handle()
    {
        $this->info('Verification des prix wishlist...');
        
        $nbAlertes = WishlistService::verifierAlertesPrix();
        
        $this->info("$nbAlertes alerte(s) envoyee(s)");
        
        return 0;
    }
}