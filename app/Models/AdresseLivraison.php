<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdresseLivraison extends Model
{
    protected $table = 'adresses_livraison';

    protected $fillable = [
        'utilisateur_id', 'nom_complet', 'telephone', 'adresse',
        'ville', 'code_postal', 'instructions', 'est_defaut',
    ];

    protected $casts = ['est_defaut' => 'boolean'];

    public function utilisateur(): BelongsTo { return $this->belongsTo(User::class, 'utilisateur_id'); }
    public function commandes(): HasMany { return $this->hasMany(Commande::class, 'adresse_livraison_id'); }
}