<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistAlerte extends Model
{
    protected $table = 'wishlist_alertes';

    protected $fillable = [
        'wishlist_id',
        'ancien_prix',
        'nouveau_prix',
        'pourcentage_reduction',
        'est_lue',
    ];

    protected $casts = [
        'ancien_prix' => 'decimal:2',
        'nouveau_prix' => 'decimal:2',
        'pourcentage_reduction' => 'decimal:2',
        'est_lue' => 'boolean',
    ];

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class, 'wishlist_id');
    }
}