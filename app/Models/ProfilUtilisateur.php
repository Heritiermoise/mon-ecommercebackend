<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfilUtilisateur extends Model
{
    protected $table = 'profils_utilisateurs';
    
    protected $fillable = [
        'utilisateur_id', 'avatar', 'adresse', 'ville',
        'pays', 'latitude', 'longitude'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}