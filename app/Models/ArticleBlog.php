<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ArticleBlog extends Model
{
    protected $table = 'articles_blog';
    
    protected $fillable = [
        'titre', 'slug', 'contenu', 'excerpt', 'image_couverture',
        'auteur_id', 'categorie', 'tags', 'nombre_vues',
        'statut', 'date_publication'
    ];

    protected $casts = [
        'nombre_vues' => 'integer',
        'date_publication' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->titre) . '-' . uniqid();
            }
        });
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function scopePublies($query)
    {
        return $query->where('statut', 'publie')
                     ->where('date_publication', '<=', now());
    }

    public function incrementerVues()
    {
        $this->increment('nombre_vues');
    }
}