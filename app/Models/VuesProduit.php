<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VuesProduit extends Model
{
    use HasFactory;

    protected $table = 'vues_produits';

    public $timestamps = false;

    protected $fillable = [
        'produit_id', 'utilisateur_id', 'session_id',
        'ip_address', 'user_agent', 'consulte_le'
    ];

    protected $casts = [
        'consulte_le' => 'datetime',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}