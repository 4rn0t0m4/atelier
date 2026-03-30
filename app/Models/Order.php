<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'number', 'status',
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
}
