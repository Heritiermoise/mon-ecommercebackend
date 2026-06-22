<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListeSouhait extends Model
{
    use HasFactory;

    protected $table = 'listes_souhaits';

    public $timestamps = false;

    protected $fillable = ['utilisateur_id', 'produit_id', 'ajoute_le'];

    protected $casts = [
        'ajoute_le' => 'datetime',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}