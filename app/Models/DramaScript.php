<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DramaScript extends Model
{
    protected $fillable = [
        'title',
        'genre',
        'synopsis',
        'status',
        'characters',
        'scenes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'characters' => 'array',
            'scenes' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
