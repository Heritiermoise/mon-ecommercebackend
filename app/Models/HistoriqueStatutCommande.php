<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueStatutCommande extends Model
{
    use HasFactory;

    protected $table = 'historique_statuts_commandes';

    public $timestamps = false;

    protected $fillable = [
        'commande_id', 'ancien_statut', 'nouveau_statut',
        'modifie_par', 'commentaire', 'cree_le'
    ];

    protected $casts = [
        'cree_le' => 'datetime',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function modifiePar()
    {
        return $this->belongsTo(User::class, 'modifie_par');
    }
}