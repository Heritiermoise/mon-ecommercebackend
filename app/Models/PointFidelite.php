<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointFidelite extends Model
{
    use HasFactory;

    protected $table = 'points_fidelite';

    protected $fillable = [
        'utilisateur_id', 'points', 'type', 'description',
        'points_montant', 'commande_id'
    ];

    protected $casts = [
        'points' => 'integer',
        'points_montant' => 'integer',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}