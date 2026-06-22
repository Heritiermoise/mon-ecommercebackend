<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodePromo extends Model
{
    use HasFactory;

    protected $table = 'codes_promo';

    protected $fillable = [
        'code', 'description', 'type_reduction', 'valeur_reduction',
        'montant_minimum', 'montant_maximum', 'utilisation_max',
        'utilisation_par_user', 'nombre_utilisations', 'statut',
        'date_debut', 'date_fin', 'categorie_id'
    ];

    protected $casts = [
        'valeur_reduction' => 'decimal:2',
        'montant_minimum' => 'decimal:2',
        'montant_maximum' => 'decimal:2',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function utilisations()
    {
        return $this->hasMany(UtilisationCodePromo::class, 'code_promo_id');
    }

    public function estValide()
    {
        if ($this->statut !== 'actif') return false;
        if ($this->date_debut && $this->date_debut->isFuture()) return false;
        if ($this->date_fin && $this->date_fin->isPast()) return false;
        if ($this->utilisation_max > 0 && $this->nombre_utilisations >= $this->utilisation_max) return false;
        return true;
    }

    public function calculerReduction($montant)
    {
        if (!$this->estValide()) return 0;
        if ($montant < $this->montant_minimum) return 0;

        $reduction = 0;
        if ($this->type_reduction === 'pourcentage') {
            $reduction = $montant * ($this->valeur_reduction / 100);
        } elseif ($this->type_reduction === 'montant_fixe') {
            $reduction = $this->valeur_reduction;
        }

        if ($this->montant_maximum && $reduction > $this->montant_maximum) {
            $reduction = $this->montant_maximum;
        }

        return $reduction;
    }
}