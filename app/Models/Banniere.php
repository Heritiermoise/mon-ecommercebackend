<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banniere extends Model
{
    use HasFactory;

    protected $table = 'bannieres';

    public $timestamps = false;

    protected $fillable = [
        'titre', 'sous_titre', 'texte_bouton', 'lien_bouton',
        'image_url', 'position', 'statut', 'date_debut', 'date_fin', 'cree_le'
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'cree_le' => 'datetime',
    ];

    public function scopeActives($query)
    {
        return $query->where('statut', 'actif')
            ->where(function($q) {
                $q->whereNull('date_debut')->orWhere('date_debut', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now());
            })
            ->orderBy('position');
    }
}