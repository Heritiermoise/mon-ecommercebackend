<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametreSite extends Model
{
    use HasFactory;

    protected $table = 'parametres_site';

    public $timestamps = false;

    protected $fillable = ['cle', 'valeur', 'type', 'categorie', 'description', 'modifie_le'];

    protected $casts = [
        'modifie_le' => 'datetime',
    ];

    public static function get($cle, $default = null)
    {
        $parametre = self::where('cle', $cle)->first();
        return $parametre ? $parametre->valeur : $default;
    }

    public static function set($cle, $valeur)
    {
        return self::updateOrCreate(
            ['cle' => $cle],
            ['valeur' => $valeur, 'modifie_le' => now()]
        );
    }
}