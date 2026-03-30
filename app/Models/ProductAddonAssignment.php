<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAddonAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = ['group_id', 'product_id'];

    public function group()
    {
        return $this->belongsTo(ProductAddonGroup::class, 'group_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
