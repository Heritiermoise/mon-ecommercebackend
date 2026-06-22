<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneLivraison extends Model
{
    protected $table = 'zones_livraison';
    
    public $timestamps = false;
    
    protected $fillable = [
        'nom', 'pays', 'ville', 'code_postal',
        'frais_livraison', 'delai_estime', 'statut'
    ];

    protected $casts = [
        'frais_livraison' => 'decimal:2',
    ];

    public function scopeActives($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeParVille($query, $ville)
    {
        return $query->where('ville', $ville);
    }

    public function scopeParPays($query, $pays)
    {
        return $query->where('pays', $pays);
    }
}