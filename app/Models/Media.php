<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'filename', 'original_filename', 'disk', 'path', 'url',
        'mime_type', 'size', 'width', 'height', 'alt', 'title',
    ];

    public function getUrlAttribute($value): string
    {
        if (app()->environment('local') && str_starts_with($value, 'https://www.atelier-aubin.fr/')) {
            return str_replace('https://www.atelier-aubin.fr', '', $value);
        }

        return $value;
    }
}
