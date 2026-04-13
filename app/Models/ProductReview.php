<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'author_name', 'author_email',
        'rating', 'content', 'photos', 'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'photos' => 'array',
        'is_approved' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isVerifiedPurchase(): bool
    {
        if (! $this->user_id) {
            return false;
        }

        return Order::where('user_id', $this->user_id)
            ->whereIn('status', ['processing', 'completed', 'shipped'])
            ->whereHas('items', fn ($q) => $q->where('product_id', $this->product_id))
            ->exists();
    }
}
