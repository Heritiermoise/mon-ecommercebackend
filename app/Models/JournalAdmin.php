<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalAdmin extends Model
{
    use HasFactory;

    protected $table = 'journaux_admin';

    public $timestamps = false;

    protected $fillable = [
        'administrateur_id', 'action', 'type_cible',
        'id_cible', 'details', 'ip_address', 'cree_le'
    ];

    protected $casts = [
        'details' => 'array',
        'cree_le' => 'datetime',
    ];

    public function administrateur()
    {
        return $this->belongsTo(User::class, 'administrateur_id');
    }
}
