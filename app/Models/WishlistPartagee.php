<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WishlistPartagee extends Model
{
    protected $table = 'wishlist_partagees';

    protected $fillable = [
        'utilisateur_id',
        'token',
        'nom',
        'est_publique',
        'expire_le',
        'nb_vues',
    ];

    protected $casts = [
        'est_publique' => 'boolean',
        'expire_le' => 'datetime',
        'nb_vues' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->token) {
                $model->token = Str::random(64);
            }
        });
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function getWishlistItems()
    {
        return Wishlist::where('utilisateur_id', $this->utilisateur_id)
            ->with('produit.categorie', 'produit.marque', 'produit.imagePrincipale')
            ->get();
    }

    public static function getByToken($token)
    {
        return self::where('token', $token)
            ->where(function($q) {
                $q->whereNull('expire_le')
                  ->orWhere('expire_le', '>', now());
            })
            ->first();
    }

    public function incrementerVues()
    {
        $this->increment('nb_vues');
    }
}