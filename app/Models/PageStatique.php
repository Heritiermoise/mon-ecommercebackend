<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageStatique extends Model
{
    protected $table = 'pages_statiques';
    
    protected $fillable = [
        'titre', 'slug', 'contenu', 'statut'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->titre);
            }
        });
    }

    public function scopeActives($query)
    {
        return $query->where('statut', 'actif');
    }

    public static function getBySlug($slug)
    {
        return self::where('slug', $slug)->where('statut', 'actif')->first();
    }
}