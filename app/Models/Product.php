<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'short_description', 'description',
        'price', 'sale_price', 'sku', 'stock_quantity', 'manage_stock',
        'stock_status', 'weight', 'is_active', 'is_featured',
        'excluded_global_group_ids',
        'meta_title', 'meta_description', 'featured_image_id',
        'gallery_image_ids', 'total_sales',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'manage_stock' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'excluded_global_group_ids' => 'array',
        'gallery_image_ids' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function featuredImage()
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true);
    }

    public function tags()
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag', 'product_id', 'tag_id');
    }

    public function addonAssignments()
    {
        return $this->hasMany(ProductAddonAssignment::class);
    }

    public function addonGroups()
    {
        return $this->belongsToMany(ProductAddonGroup::class, 'product_addon_assignments', 'product_id', 'group_id');
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->sale_price ?? $this->price;
    }

    public function isInStock(): bool
    {
        if (! $this->manage_stock) {
            return $this->stock_status === 'instock';
        }

        return $this->stock_quantity > 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function url(): string
    {
        $category = $this->category;
        if ($category && $category->parent) {
            return url("/boutique/{$category->parent->slug}/{$category->slug}/{$this->slug}");
        }
        if ($category) {
            return url("/boutique/{$category->slug}/{$this->slug}");
        }

        return url("/boutique/{$this->slug}");
    }

    public function getAllAddonGroups()
    {
        $productGroups = $this->addonGroups()->with('addons')->orderBy('sort_order')->get();

        $excludedIds = $this->excluded_global_group_ids ?? [];

        $globalGroups = ProductAddonGroup::where('is_global', true)
            ->when(! empty($excludedIds), fn ($q) => $q->whereNotIn('id', $excludedIds))
            ->with('addons')
            ->orderBy('sort_order')
            ->get()
            ->filter(function ($group) {
                if (empty($group->restrict_to_categories)) {
                    return true;
                }

                return in_array($this->category_id, $group->restrict_to_categories);
            });

        return $globalGroups->merge($productGroups)->sortBy('sort_order');
    }
}
