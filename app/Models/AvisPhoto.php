<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisPhoto extends Model
{
    protected $table = 'avis_photos';

    protected $fillable = [
        'avis_id',
        'url_image',
        'chemin_fichier',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    public function avis(): BelongsTo
    {
        return $this->belongsTo(Avis::class, 'avis_id');
    }
}