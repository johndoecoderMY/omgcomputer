<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaContentPost extends Model
{
    protected $fillable = [
        'created_by',
        'platform',
        'media_type',
        'caption',
        'media_path',
        'status',
        'scheduled_at',
        'published_at',
        'meta_post_id',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
