<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisSignalement extends Model
{
    protected $table = 'avis_signalements';

    protected $fillable = [
        'avis_id',
        'utilisateur_id',
        'motif',
        'details',
        'est_traite',
    ];

    protected $casts = [
        'est_traite' => 'boolean',
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