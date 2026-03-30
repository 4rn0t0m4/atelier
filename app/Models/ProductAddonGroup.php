<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAddonGroup extends Model
{
    protected $fillable = [
        'name', 'description', 'is_global', 'restrict_to_categories', 'sort_order',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'restrict_to_categories' => 'array',
    ];

    public function addons()
    {
        return $this->hasMany(ProductAddon::class, 'group_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_addon_assignments', 'group_id', 'product_id');
    }
}
