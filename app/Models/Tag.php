<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $table = 'tags';

    protected $fillable = ['nom', 'slug'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($tag) {
            if (!$tag->slug) {
                $tag->slug = Str::slug($tag->nom);
            }
        });
    }

    public function produits(): BelongsToMany
    {
        return $this->belongsToMany(Produit::class, 'produit_tags');
    }
}