<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Categorie extends Model {
    protected $table = 'categories';
    protected $fillable = ['nom','slug','parent_id'];
    protected static function boot() {
        parent::boot();
        static::creating(function ($c) { if (empty($c->slug)) $c->slug = Str::slug($c->nom); });
    }
    public function parent() { return $this->belongsTo(Categorie::class, 'parent_id'); }
    public function enfants() { return $this->hasMany(Categorie::class, 'parent_id'); }
    public function produits() { return $this->hasMany(Produit::class, 'categorie_id'); }
}