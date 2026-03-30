<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $fillable = [
        'parent_id', 'name', 'slug', 'description',
        'featured_image_id', 'sort_order', 'meta_title', 'meta_description',
    ];

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function url(): string
    {
        if ($this->parent) {
            return url("/boutique/{$this->parent->slug}/{$this->slug}");
        }

        return url("/boutique/{$this->slug}");
    }

    /**
     * Retourne les IDs de cette catégorie + ses enfants.
     */
    public function familyIds(): array
    {
        return array_merge(
            [$this->id],
            $this->children()->pluck('id')->toArray()
        );
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function featuredImage()
    {
        return $this->belongsTo(Media::class, 'featured_image_id');
    }
}
