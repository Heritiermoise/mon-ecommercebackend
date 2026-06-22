<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parrainage extends Model
{
    protected $table = 'parrainages';
    
    public $timestamps = false;
    
    protected $fillable = [
        'parrain_id', 'filleul_id', 'code_parrainage',
        'points_gagnes', 'statut'
    ];

    protected $casts = [
        'points_gagnes' => 'integer',
    ];

    public function parrain()
    {
        return $this->belongsTo(User::class, 'parrain_id');
    }

    public function filleul()
    {
        return $this->belongsTo(User::class, 'filleul_id');
    }

    public function scopeValides($query)
    {
        return $query->where('statut', 'valide');
    }

    public function valider($points = 100)
    {
        $this->update([
            'statut' => 'valide',
            'points_gagnes' => $points,
        ]);
    }
}