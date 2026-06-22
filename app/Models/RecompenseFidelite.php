<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecompenseFidelite extends Model
{
    use HasFactory;

    protected $table = 'recompenses_fidelite';

    protected $fillable = [
        'nom',
        'description',
        'points_necessaires',
        'type_reduction',
        'valeur_reduction',
        'stock_disponible',
        'statut',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'points_necessaires' => 'integer',
        'valeur_reduction' => 'decimal:2',
        'stock_disponible' => 'integer',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Scope : récompenses actives
     */
    public function scopeActives($query)
    {
        return $query->where('statut', 'actif')
                     ->where('stock_disponible', '>', 0);
    }

    /**
     * Scope : récompenses disponibles pour un certain nombre de points
     */
    public function scopeDisponiblesPour($query, $points)
    {
        return $query->where('points_necessaires', '<=', $points);
    }

    /**
     * Helper : vérifier si la récompense est disponible
     */
    public function estDisponible()
    {
        return $this->statut === 'actif' 
            && $this->stock_disponible > 0;
    }

    /**
     * Helper : vérifier si l'utilisateur a assez de points
     */
    public function utilisateurAssezDePoints($pointsUtilisateur)
    {
        return $pointsUtilisateur >= $this->points_necessaires;
    }

    /**
     * Relation : utilisations de cette récompense
     */
    public function utilisations()
    {
        return $this->hasMany(UtilisationRecompense::class, 'recompense_id');
    }

    /**
     * Décrémenter le stock
     */
    public function decrementerStock($quantite = 1)
    {
        if ($this->stock_disponible >= $quantite) {
            $this->decrement('stock_disponible', $quantite);
            return true;
        }
        return false;
    }
}