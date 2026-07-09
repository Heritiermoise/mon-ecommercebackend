<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Produit extends Model
{
    protected $table = 'produits';

    protected $fillable = [
        'categorie_id', 'marque_id', 'nom', 'slug', 'description',
        'prix', 'prix_remise', 'quantite_stock', 'statut',
        'note_moyenne', 'nombre_avis',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'prix_remise' => 'decimal:2',
        'quantite_stock' => 'integer',
        'note_moyenne' => 'decimal:2',
        'nombre_avis' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($produit) {
            if (!$produit->slug) {
                $produit->slug = Str::slug($produit->nom) . '-' . uniqid();
            }
        });
    }

    // ============================================
    // RELATIONS
    // ============================================

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class, 'marque_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ImageProduit::class, 'produit_id');
    }

    public function avis(): HasMany
    {
        return $this->hasMany(\App\Models\Avis::class, 'produit_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Tag::class, 'produit_tags');
    }

    public function couleurs(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Couleur::class, 'produit_couleurs');
    }

    public function tailles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Taille::class, 'produit_tailles')->withPivot('stock');
    }

    public function imagePrincipale(): HasOne
    {
        return $this->hasOne(ImageProduit::class, 'produit_id')->where('est_principale', true);
    }

    public function getImageDisplayUrlAttribute(): string
    {
        $image = null;

        if ($this->relationLoaded('imagePrincipale')) {
            $image = $this->imagePrincipale;
        } else {
            $image = $this->imagePrincipale()->first();
        }

        if (!$image && $this->relationLoaded('images')) {
            $image = $this->images->sortBy('ordre')->first();
        }

        if ($image && !empty($image->url_image)) {
            return $image->url_image;
        }

        return asset('images/product-placeholder.svg');
    }

    // ============================================
    // MÉTHODES DE PRIX - IMPORTANT !
    // ============================================

    /**
     * Accessor Laravel : $produit->prix_final
     */
    public function getPrixFinalAttribute()
    {
        if ($this->prix_remise && $this->prix_remise > 0 && $this->prix_remise < $this->prix) {
            return (float) $this->prix_remise;
        }
        return (float) $this->prix;
    }

    /**
     * MÉTHODE APPELABLE DIRECTEMENT : $produit->getPrixFinal()
     * C'est ce qui est appelé dans le panier !
     */
    public function getPrixFinal()
    {
        return $this->prix_final;
    }

    /**
     * Vérifier si le produit est en promotion
     */
    public function estEnPromotion()
    {
        return $this->prix_remise && $this->prix_remise > 0 && $this->prix_remise < $this->prix;
    }

    /**
     * Pourcentage de réduction
     */
    public function getPourcentageReductionAttribute()
    {
        if ($this->estEnPromotion()) {
            return round((($this->prix - $this->prix_remise) / $this->prix) * 100);
        }
        return 0;
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeEnStock($query)
    {
        return $query->where('quantite_stock', '>', 0);
    }
}