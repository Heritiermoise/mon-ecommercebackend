<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduitAchetes extends Model
{
    protected $table = 'produits_achetes';

    protected $fillable = ['produit_id', 'commande_id', 'quantite', 'prix_unitaire'];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public static function getSouventAchetesEnsemble($produitId, $limit = 4)
    {
        return self::select('produits_achetes.produit_id')
            ->join('produits_achetes as pa2', 'produits_achetes.commande_id', '=', 'pa2.commande_id')
            ->where('pa2.produit_id', $produitId)
            ->where('produits_achetes.produit_id', '!=', $produitId)
            ->groupBy('produits_achetes.produit_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('produit_id');
    }
}