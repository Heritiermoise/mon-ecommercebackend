<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RechercheRecente extends Model
{
    protected $table = 'recherches_recentes';

    protected $fillable = [
        'utilisateur_id',
        'session_id',
        'terme',
        'ip_address',
        'nb_resultats',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    public static function enregistrer($terme, $nbResultats = 0, $userId = null, $sessionId = null, $ip = null)
    {
        if (empty(trim($terme))) return;

        self::create([
            'utilisateur_id' => $userId,
            'session_id' => $sessionId,
            'terme' => trim($terme),
            'ip_address' => $ip,
            'nb_resultats' => $nbResultats,
        ]);

        self::where('created_at', '<', now()->subDays(30))->delete();
    }

    public static function getSuggestions($terme, $limit = 5)
    {
        return self::select('terme')
            ->where('terme', 'like', '%' . $terme . '%')
            ->groupBy('terme')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('terme');
    }
}