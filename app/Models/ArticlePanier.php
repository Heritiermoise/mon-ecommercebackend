<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticlePanier extends Model
{
    protected $table = 'articles_panier';
    protected $fillable = ['panier_id', 'produit_id', 'quantite', 'prix_unitaire'];
    protected $casts = ['quantite' => 'integer', 'prix_unitaire' => 'decimal:2'];

    public function panier() { return $this->belongsTo(Panier::class, 'panier_id'); }
    public function produit() { return $this->belongsTo(Produit::class, 'produit_id'); }
}