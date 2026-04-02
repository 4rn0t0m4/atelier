<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRule extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'coupon_code', 'is_active', 'type', 'discount_type', 'discount_amount',
        'target_categories', 'target_products',
        'min_cart_value', 'max_cart_value', 'min_quantity', 'max_quantity',
        'starts_at', 'ends_at', 'stackable', 'sort_order',
        'usage_limit', 'usage_count', 'free_shipping', 'exclude_sale_items',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'discount_amount' => 'decimal:2',
        'target_categories' => 'array',
        'target_products' => 'array',
        'min_cart_value' => 'decimal:2',
        'max_cart_value' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'stackable' => 'boolean',
        'free_shipping' => 'boolean',
        'exclude_sale_items' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && now()->gt($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }
}
