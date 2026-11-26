<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Demande de reexpedition en point relais d'une commande retournee a l'expediteur.
 * Le client choisit un nouveau relais et regle les frais via un lien a usage unique.
 */
class OrderReshipment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id', 'token', 'amount', 'status',
        'payment_method', 'stripe_payment_intent_id', 'paypal_order_id', 'paid_at',
        'relay_point_code', 'relay_network', 'relay_point_name', 'relay_point_address',
        'previous_relay_point_code', 'previous_boxtal_shipping_order_id', 'previous_tracking_number',
        'email_sent_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function getUrlAttribute(): string
    {
        return route('reshipment.show', $this->token);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Payée',
            self::STATUS_CANCELLED => 'Annulée',
            default => 'En attente de paiement',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 2, ',', ' ') . ' €';
    }

    /**
     * Libelle du reseau du relais choisi (Mondial Relay, Chronopost...).
     */
    public function getRelayNetworkLabelAttribute(): ?string
    {
        return match ($this->relay_network) {
            'MONR_NETWORK' => 'Mondial Relay',
            'CHRP_NETWORK' => 'Chronopost',
            default => $this->relay_network,
        };
    }
}
