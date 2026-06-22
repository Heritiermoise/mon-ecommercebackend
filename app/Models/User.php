<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $table = 'utilisateurs';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nom', 'email', 'telephone', 'mot_de_passe_hash',
        'role', 'statut', 'two_factor_enabled', 'two_factor_secret',
        'two_factor_last_verified', 'current_session_token',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['mot_de_passe_hash', 'two_factor_secret'];

    protected $casts = [
        'two_factor_enabled' => 'boolean',
        'two_factor_last_verified' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function getJWTIdentifier() { return $this->getKey(); }
    public function getJWTCustomClaims() { return []; }
    public function getAuthPassword() { return $this->mot_de_passe_hash; }

    public function commandes(): HasMany { return $this->hasMany(Commande::class, 'utilisateur_id'); }
    public function panier(): HasMany { return $this->hasMany(Panier::class, 'utilisateur_id'); }
    public function adresses(): HasMany { return $this->hasMany(AdresseLivraison::class, 'utilisateur_id'); }
    public function notifications(): HasMany { return $this->hasMany(Notification::class, 'utilisateur_id'); }
    public function wishlists(): HasMany { return $this->hasMany(Wishlist::class, 'utilisateur_id'); }

    public function getPointsFideliteTotal() {
        return $this->commandes()->where('statut_paiement', 'paye')->sum('montant_total');
    }
    public function getTotalDepense() {
        return $this->commandes()->where('statut_paiement', 'paye')->sum('montant_total');
    }
    public function isAdmin() { return in_array($this->role, ['administrateur', 'super_administrateur']); }
    public function isSuperAdmin() { return $this->role === 'super_administrateur'; }
}