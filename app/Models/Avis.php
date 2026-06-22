<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avis extends Model
{
    protected $table = 'avis';

    protected $fillable = [
        'produit_id',
        'utilisateur_id',
        'commande_id',
        'note',
        'titre',
        'commentaire',
        'est_verifie',
        'est_approuve',
        'nb_utile',
        'nb_inutile',
        'date_publication',
    ];

    protected $casts = [
        'note' => 'integer',
        'est_verifie' => 'boolean',
        'est_approuve' => 'boolean',
        'nb_utile' => 'integer',
        'nb_inutile' => 'integer',
        'date_publication' => 'datetime',
    ];

    // ============================================
    // RELATIONS
    // ============================================

    /**
     * Produit concerné par l'avis
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    /**
     * Utilisateur qui a laissé l'avis
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Commande associée (si achat vérifié)
     */
    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    /**
     * Photos de l'avis
     */
    public function photos(): HasMany
    {
        return $this->hasMany(AvisPhoto::class, 'avis_id');
    }

    /**
     * Réponses à l'avis
     */
    public function reponses(): HasMany
    {
        return $this->hasMany(AvisReponse::class, 'avis_id');
    }

    /**
     * Signalements de l'avis
     */
    public function signalements(): HasMany
    {
        return $this->hasMany(AvisSignalement::class, 'avis_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeApprouves($query)
    {
        return $query->where('est_approuve', true);
    }

    public function scopeVerifies($query)
    {
        return $query->where('est_verifie', true);
    }

    public function scopeAvecPhotos($query)
    {
        return $query->has('photos');
    }

    // ============================================
    // MÉTHODES UTILITAIRES
    // ============================================

    /**
     * Calculer la note moyenne pour un produit
     */
    public static function calculerNoteMoyenne($produitId)
    {
        return self::where('produit_id', $produitId)
            ->where('est_approuve', true)
            ->avg('note') ?? 0;
    }

    /**
     * Compter les avis pour un produit
     */
    public static function compterAvis($produitId)
    {
        return self::where('produit_id', $produitId)
            ->where('est_approuve', true)
            ->count();
    }

    /**
     * Vérifier si l'utilisateur a déjà laissé un avis
     */
    public static function utilisateurAEcritAvis($produitId, $utilisateurId)
    {
        return self::where('produit_id', $produitId)
            ->where('utilisateur_id', $utilisateurId)
            ->exists();
    }
}