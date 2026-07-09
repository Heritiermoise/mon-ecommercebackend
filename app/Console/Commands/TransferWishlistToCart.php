<?php

namespace App\Console\Commands;

use App\Services\WishlistService;
use Illuminate\Console\Command;

class TransferWishlistToCart extends Command
{
    protected $signature = 'wishlist:transfer-to-cart {userId? : ID utilisateur}';
    protected $description = 'Transférer une wishlist vers le panier actif';

    public function handle()
    {
        $userId = $this->argument('userId');

        if (!$userId) {
            $this->error('userId requis');
            return 1;
        }

        $result = WishlistService::transfererAuPanier((int) $userId);

        if (!$result['success']) {
            $this->error($result['message']);
            return 1;
        }

        $this->info($result['message']);
        return 0;
    }
}
