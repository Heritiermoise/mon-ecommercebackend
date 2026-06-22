<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Commande extends Model
{
    protected $table = 'commandes';

    protected $fillable = [
        'utilisateur_id',
        'numero_commande',
        'montant_total',
        'frais_livraison',
        'reduction',
        'adresse_livraison_id',
        'statut',
        'statut_paiement',
        'note_client',
        'note_admin',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'reduction' => 'decimal:2',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function adresseLivraison(): BelongsTo
    {
        return $this->belongsTo(AdresseLivraison::class, 'adresse_livraison_id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(ArticleCommande::class, 'commande_id');
    }

    public function paiement(): HasOne
    {
        return $this->hasOne(Paiement::class, 'commande_id');
    }

    public function getTotalFinalAttribute()
    {
        return $this->montant_total + $this->frais_livraison - $this->reduction;
    }
}