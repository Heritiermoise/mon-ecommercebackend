<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faq';
    
    public $timestamps = false;
    
    protected $fillable = [
        'question', 'reponse', 'categorie', 'position', 'statut'
    ];

    public function scopeActives($query)
    {
        return $query->where('statut', 'actif')->orderBy('position');
    }

    public function scopeParCategorie($query, $categorie)
    {
        return $query->where('categorie', $categorie);
    }
}