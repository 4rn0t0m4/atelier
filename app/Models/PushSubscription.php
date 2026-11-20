<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Abonnement Web Push d'un appareil (navigateur ou app installee) d'un admin.
 */
class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint', 'endpoint_hash',
        'public_key', 'auth_token', 'content_encoding', 'user_agent',
    ];

    protected $hidden = ['public_key', 'auth_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hashEndpoint(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Appareils des comptes encore administrateurs.
     */
    public function scopeForAdmins(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $q) => $q->where('is_admin', true));
    }
}
