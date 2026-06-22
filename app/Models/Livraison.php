<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;

    protected $table = 'livraisons';

    protected $fillable = [
        'commande_id', 'statut', 'agent_livraison',
        'code_suivi', 'transporteur', 'notes'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}