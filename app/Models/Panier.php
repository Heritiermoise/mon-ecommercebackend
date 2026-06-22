<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Panier extends Model
{
    use HasFactory;

    protected $table = 'paniers';

    protected $fillable = ['utilisateur_id', 'statut', 'session_id'];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function articles()
    {
        return $this->hasMany(ArticlePanier::class, 'panier_id');
    }

    public function getTotal()
    {
        return $this->articles->sum(fn($a) => $a->quantite * $a->prix_unitaire);
    }

    public function getNombreArticles()
    {
        return $this->articles->sum('quantite');
    }
}