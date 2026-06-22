<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'utilisateur_id',
        'type',
        'titre',
        'message',
        'lien',
        'lu',
    ];

    protected $casts = [
        'lu' => 'boolean',
    ];

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    public function scopePourUtilisateur($query, $userId)
    {
        return $query->where('utilisateur_id', $userId);
    }
}