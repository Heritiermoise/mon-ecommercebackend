<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageSupport extends Model
{
    use HasFactory;

    protected $table = 'messages_support';

    public $timestamps = false;

    protected $fillable = [
        'utilisateur_id', 'nom', 'email', 'telephone', 'sujet',
        'message', 'statut', 'priorite', 'repondu_par',
        'reponse', 'date_reponse', 'cree_le'
    ];

    protected $casts = [
        'date_reponse' => 'datetime',
        'cree_le' => 'datetime',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function reponduPar()
    {
        return $this->belongsTo(User::class, 'repondu_par');
    }

    public function scopeNouveaux($query)
    {
        return $query->where('statut', 'nouveau');
    }
}