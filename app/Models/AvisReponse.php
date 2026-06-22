<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisReponse extends Model
{
    protected $table = 'avis_reponses';

    protected $fillable = [
        'avis_id',
        'utilisateur_id',
        'contenu',
        'est_admin',
    ];

    protected $casts = [
        'est_admin' => 'boolean',
    ];

    public function avis()
    {
        return $this->belongsTo(Avis::class, 'avis_id');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}