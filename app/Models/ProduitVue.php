<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProduitVue extends Model
{
    protected $table = 'produits_vues';

    protected $fillable = ['produit_id', 'utilisateur_id', 'session_id', 'ip_address'];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public static function enregistrer($produitId, $userId = null, $sessionId = null, $ip = null)
    {
        self::create([
            'produit_id' => $produitId,
            'utilisateur_id' => $userId,
            'session_id' => $sessionId,
            'ip_address' => $ip,
        ]);
    }

    public static function getRecemmentVus($userId = null, $sessionId = null, $limit = 10)
    {
        $query = self::with('produit');
        
        if ($userId) {
            $query->where('utilisateur_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->orderByDesc('created_at')
            ->limit($limit * 2)
            ->get()
            ->unique('produit_id')
            ->take($limit)
            ->pluck('produit')
            ->filter();
    }
}