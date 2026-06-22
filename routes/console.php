<?php

use Illuminate\Support\Facades\Schedule;

// Sauvegarde automatique quotidienne a 2h du matin
Schedule::command('backup:auto --encrypt')->dailyAt('02:00');

// Nettoyage des logs de securite chaque dimanche a 3h
Schedule::command('security:cleanup-logs')->weeklyOn(0, '03:00');

// Verification d'integrite des donnees chaque jour a 6h
Schedule::call(function () {
    $issues = \App\Services\SecurityService::verifyDataIntegrity();
    if (count($issues) > 0) {
        \Illuminate\Support\Facades\Log::warning('Problemes d integrite detectes: ' . implode(', ', $issues));
    }
})->dailyAt('06:00');
Schedule::command('wishlist:check-prices')->hourly();
