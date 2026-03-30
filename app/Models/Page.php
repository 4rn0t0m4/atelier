<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'content',
        'meta_title', 'meta_description',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];
}
