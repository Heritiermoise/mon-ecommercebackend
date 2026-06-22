<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MethodePaiement extends Model
{
    protected $table = 'methodes_paiement';
    
    public $timestamps = false;
    
    protected $fillable = [
        'nom', 'code', 'description', 'logo_url',
        'frais_supplementaires', 'statut', 'configuration', 'position'
    ];

    protected $casts = [
        'frais_supplementaires' => 'decimal:2',
        'configuration' => 'array',
    ];

    public function scopeActives($query)
    {
        return $query->where('statut', 'actif')->orderBy('position');
    }

    public function isDisponible()
    {
        return $this->statut === 'actif';
    }
}