<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Couleur extends Model
{
    protected $table = 'couleurs';

    protected $fillable = ['nom', 'code_hex'];

    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'produit_couleurs');
    }
}