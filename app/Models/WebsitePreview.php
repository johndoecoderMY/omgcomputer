<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePreview extends Model
{
    protected $fillable = [
        'title',
        'category',
        'description',
        'preview_url',
        'image_path',
        'gallery_images',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'gallery_images' => 'array',
        ];
    }
}
