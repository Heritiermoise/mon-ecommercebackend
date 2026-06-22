<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtilisationRecompense extends Model
{
    use HasFactory;

    protected $table = 'utilisations_recompenses';

    protected $fillable = [
        'utilisateur_id',
        'recompense_id',
        'points_utilises',
        'statut',
        'date_utilisation',
    ];

    protected $casts = [
        'points_utilises' => 'integer',
        'date_utilisation' => 'datetime',
    ];

    // Relations
    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function recompense()
    {
        return $this->belongsTo(RecompenseFidelite::class, 'recompense_id');
    }

    // Scope : utilisations réussies
    public function scopeReussies($query)
    {
        return $query->where('statut', 'reussi');
    }

    // Scope : par utilisateur
    public function scopeParUtilisateur($query, $userId)
    {
        return $query->where('utilisateur_id', $userId);
    }
}