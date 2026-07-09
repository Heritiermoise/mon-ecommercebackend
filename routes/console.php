<?php

use Illuminate\Support\Facades\Schedule;

// Sauvegarde automatique quotidienne a 2h du matin
Schedule::command('backup:auto --encrypt')->dailyAt('02:00');

// Nettoyage des logs de securite chaque dimanche a 3h
Schedule::command('security:cleanup-logs')->weeklyOn(0, '03:00');

// Nettoyage des recherches recentes (equivalent event SQL)
Schedule::command('searches:cleanup --days=30')->dailyAt('01:00');

// Nettoyage des sessions expirées (equivalent event SQL)
Schedule::command('sessions:cleanup-expired --days=7')->dailyAt('02:30');

// Recalcul automatique des notes produits (equivalent event + trigger avis)
Schedule::command('products:recompute-ratings')->hourly();

// Archivage des anciennes commandes (equivalent event SQL)
Schedule::command('orders:archive-old --months=24')->monthlyOn(1, '03:30');

// Rapport stock faible (equivalent procedure alerte stock)
Schedule::command('stock:alert-report --seuil=10')->dailyAt('04:00');

// Verification d'integrite des donnees chaque jour a 6h
Schedule::call(function () {
    $data = ['scheduler' => 'daily-integrity-check'];
    $signature = \App\Services\SecurityService::generateSignature($data);

    if (!\App\Services\SecurityService::verifyDataIntegrity($data, $signature)) {
        \Illuminate\Support\Facades\Log::warning('Problemes d integrite detectes lors du check programme');
    }
})->dailyAt('06:00');
Schedule::command('wishlist:check-prices')->hourly();

// Optionnel: transfert de wishlist vers panier a la demande, pas planifie globalement.
