<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Taille extends Model
{
    protected $table = 'tailles';

    protected $fillable = ['nom', 'ordre'];

    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'produit_tailles')
            ->withPivot('stock');
    }
}