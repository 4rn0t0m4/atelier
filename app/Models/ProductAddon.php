<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    protected $fillable = [
        'group_id', 'label', 'type', 'display',
        'price', 'price_type', 'adjust_price', 'required', 'sync_qty',
        'min', 'max', 'restrictions_type', 'description', 'placeholder',
        'options', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'adjust_price' => 'boolean',
        'required' => 'boolean',
        'sync_qty' => 'boolean',
        'options' => 'array',
    ];

    public function group()
    {
        return $this->belongsTo(ProductAddonGroup::class, 'group_id');
    }
}
