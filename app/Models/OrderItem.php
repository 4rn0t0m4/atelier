<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_name', 'sku',
        'quantity', 'unit_price', 'addons_price', 'total', 'tax',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'addons_price' => 'decimal:2',
        'total' => 'decimal:2',
        'tax' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function addons()
    {
        return $this->hasMany(OrderItemAddon::class);
    }
}
