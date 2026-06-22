<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImageProduit extends Model
{
    use HasFactory;

    protected $table = 'images_produits';

    protected $fillable = ['produit_id', 'url_image', 'est_principale', 'ordre'];

    protected $casts = [
        'est_principale' => 'boolean',
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }
}