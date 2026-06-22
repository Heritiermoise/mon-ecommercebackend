<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    protected $table = 'wishlists';

    protected $fillable = [
        'utilisateur_id',
        'produit_id',
        'nom_collection',
        'note_personnelle',
        'alerte_prix',
        'prix_cible',
        'prix_ajout',
        'derniere_alerte',
    ];

    protected $casts = [
        'alerte_prix' => 'boolean',
        'prix_cible' => 'decimal:2',
        'prix_ajout' => 'decimal:2',
        'derniere_alerte' => 'datetime',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function alertes(): HasMany
    {
        return $this->hasMany(WishlistAlerte::class, 'wishlist_id');
    }

    public static function estDansWishlist($userId, $produitId): bool
    {
        return self::where('utilisateur_id', $userId)
            ->where('produit_id', $produitId)
            ->exists();
    }

    public static function ajouter($userId, $produitId, $options = [])
    {
        if (self::estDansWishlist($userId, $produitId)) {
            return self::where('utilisateur_id', $userId)
                ->where('produit_id', $produitId)
                ->first();
        }

        $produit = Produit::find($produitId);
        $prixActuel = $produit ? ($produit->prix_remise ?? $produit->prix) : 0;

        return self::create([
            'utilisateur_id' => $userId,
            'produit_id' => $produitId,
            'nom_collection' => $options['nom_collection'] ?? 'Mes favoris',
            'note_personnelle' => $options['note_personnelle'] ?? null,
            'alerte_prix' => $options['alerte_prix'] ?? false,
            'prix_cible' => $options['prix_cible'] ?? null,
            'prix_ajout' => $prixActuel,
        ]);
    }

    public static function supprimer($userId, $produitId)
    {
        return self::where('utilisateur_id', $userId)
            ->where('produit_id', $produitId)
            ->delete();
    }

    public static function countForUser($userId)
    {
        return self::where('utilisateur_id', $userId)->count();
    }

    public static function getAlertesEnAttente()
    {
        return self::where('alerte_prix', true)
            ->whereNotNull('prix_cible')
            ->with('produit')
            ->get()
            ->filter(function($wishlist) {
                if (!$wishlist->produit) return false;
                $prixActuel = $wishlist->produit->prix_remise ?? $wishlist->produit->prix;
                return $prixActuel <= $wishlist->prix_cible;
            });
    }
}