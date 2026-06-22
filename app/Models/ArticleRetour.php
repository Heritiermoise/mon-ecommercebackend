<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleRetour extends Model
{
    use HasFactory;

    protected $table = 'articles_retour';

    protected $fillable = ['retour_id', 'article_commande_id', 'quantite', 'etat'];

    protected $casts = [
        'quantite' => 'integer',
    ];

    public function retour()
    {
        return $this->belongsTo(Retour::class, 'retour_id');
    }

    public function articleCommande()
    {
        return $this->belongsTo(ArticleCommande::class, 'article_commande_id');
    }
}