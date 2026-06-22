<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtilisationCodePromo extends Model
{
    use HasFactory;

    protected $table = 'utilisations_code_promo';

    public $timestamps = false;

    protected $fillable = [
        'code_promo_id', 'utilisateur_id', 'commande_id',
        'montant_reduction', 'utilise_le'
    ];

    protected $casts = [
        'montant_reduction' => 'decimal:2',
        'utilise_le' => 'datetime',
    ];

    public function codePromo()
    {
        return $this->belongsTo(CodePromo::class, 'code_promo_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}