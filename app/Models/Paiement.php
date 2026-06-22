<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;

    protected $table = 'paiements';

    protected $fillable = [
        'commande_id', 'methode', 'id_transaction_fournisseur',
        'montant', 'frais', 'statut', 'details_paiement',
        'paye_le', 'rembourse_le'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'details_paiement' => 'array',
        'paye_le' => 'datetime',
        'rembourse_le' => 'datetime',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function isSucces()
    {
        return $this->statut === 'succes';
    }
}