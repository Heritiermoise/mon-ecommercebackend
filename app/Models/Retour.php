<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Retour extends Model
{
    use HasFactory;

    protected $table = 'retours';

    protected $fillable = [
        'numero_retour', 'commande_id', 'utilisateur_id', 'motif',
        'statut', 'montant_remboursement', 'date_demande',
        'date_traitement', 'traite_par', 'commentaire_admin'
    ];

    protected $casts = [
        'montant_remboursement' => 'decimal:2',
        'date_demande' => 'datetime',
        'date_traitement' => 'datetime',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function traitePar()
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function articles()
    {
        return $this->hasMany(ArticleRetour::class, 'retour_id');
    }
}