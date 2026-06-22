<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleCommande extends Model
{
    use HasFactory;

    protected $table = 'articles_commande';

    protected $fillable = [
        'commande_id', 'produit_id', 'produit_nom',
        'quantite', 'prix', 'prix_total'
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix' => 'decimal:2',
        'prix_total' => 'decimal:2',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}