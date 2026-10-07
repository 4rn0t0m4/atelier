<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Transporteurs dont le suivi est assure par le portail La Poste.
     */
    public const LA_POSTE_CARRIERS = ['Colissimo', 'La Poste'];

    /**
     * Cles de livraison en point relais (expedition pilotee par Boxtal).
     */
    public const RELAY_SHIPPING_KEYS = ['boxtal', 'boxtal_intl'];

    protected $fillable = [
        'user_id', 'number', 'invoice_number', 'status',
        'subtotal', 'discount_total', 'shipping_total', 'tax_total', 'total', 'currency',
        'payment_method', 'stripe_payment_intent_id', 'paypal_order_id', 'paid_at',
        'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone',
        'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode', 'billing_country',
        'shipping_first_name', 'shipping_last_name',
        'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_postcode', 'shipping_country',
        'shipping_method', 'shipping_key',
        'relay_point_code', 'relay_network',
        'tracking_number', 'tracking_carrier', 'tracking_url',
        'boxtal_shipping_order_id', 'boxtal_label_url',
        'shipped_at', 'review_requested_at',
        'customer_note', 'coupon_code',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'review_requested_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at !== null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isShipped(): bool
    {
        return $this->shipped_at !== null;
    }

    public function getBillingFullNameAttribute(): string
    {
        return trim(($this->billing_first_name ?? '') . ' ' . ($this->billing_last_name ?? ''));
    }

    public static function isLaPosteCarrier(?string $carrier): bool
    {
        $carriers = array_map('mb_strtolower', self::LA_POSTE_CARRIERS);

        return in_array(mb_strtolower(trim((string) $carrier)), $carriers, true);
    }

    public static function laPosteTrackingUrl(?string $trackingNumber): ?string
    {
        $trackingNumber = self::normalizeTrackingNumber($trackingNumber);

        return $trackingNumber !== null
            ? 'https://www.laposte.fr/outils/suivre-vos-envois?code=' . urlencode($trackingNumber)
            : null;
    }

    /**
     * Les numeros de suivi sont souvent copies-colles avec des espaces
     * (ex. « 8J 0090097889 8 »), que les transporteurs n'attendent pas.
     */
    public static function normalizeTrackingNumber(?string $trackingNumber): ?string
    {
        $trackingNumber = preg_replace('/\s+/u', '', (string) $trackingNumber);

        return $trackingNumber !== '' ? $trackingNumber : null;
    }

    public function setTrackingNumberAttribute($value): void
    {
        $this->attributes['tracking_number'] = self::normalizeTrackingNumber($value);
    }

    /**
     * Livraison en point relais (Boxtal) plutot qu'a domicile.
     */
    public function isRelayDelivery(): bool
    {
        return in_array($this->shipping_key, self::RELAY_SHIPPING_KEYS, true)
            || str_contains(mb_strtolower((string) $this->shipping_method), 'relais');
    }
}
