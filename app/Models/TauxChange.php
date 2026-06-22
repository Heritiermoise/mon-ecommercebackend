<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TauxChange extends Model
{
    protected $table = 'taux_change';

    protected $fillable = [
        'devise_source',
        'devise_cible',
        'taux',
        'est_actif',
        'date_application',
        'note',
        'modifie_par',
    ];

    protected $casts = [
        'taux' => 'decimal:4',
        'est_actif' => 'boolean',
        'date_application' => 'datetime',
    ];

    public function modifiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modifie_par');
    }

    /**
     * Obtenir le taux actif actuel
     */
    public static function getTauxActif($source = 'USD', $cible = 'CDF')
    {
        $taux = self::where('devise_source', $source)
            ->where('devise_cible', $cible)
            ->where('est_actif', true)
            ->orderBy('date_application', 'desc')
            ->first();

        return $taux ? (float) $taux->taux : 2800.0;
    }

    /**
     * Mettre à jour le taux (désactive les anciens)
     */
    public static function updateTaux($taux, $source = 'USD', $cible = 'CDF', $userId = null, $note = null)
    {
        // Désactiver les anciens taux
        self::where('devise_source', $source)
            ->where('devise_cible', $cible)
            ->where('est_actif', true)
            ->update(['est_actif' => false]);

        // Créer le nouveau taux
        return self::create([
            'devise_source' => $source,
            'devise_cible' => $cible,
            'taux' => $taux,
            'est_actif' => true,
            'date_application' => now(),
            'note' => $note,
            'modifie_par' => $userId,
        ]);
    }
}