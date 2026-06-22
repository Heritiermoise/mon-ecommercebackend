<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comparateur extends Model
{
    protected $table = 'comparateurs';
    
    public $timestamps = false;
    
    protected $fillable = [
        'session_id', 'utilisateur_id', 'produit_id'
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function scopeParUtilisateur($query, $userId)
    {
        return $query->where('utilisateur_id', $userId);
    }

    public function scopeParSession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}