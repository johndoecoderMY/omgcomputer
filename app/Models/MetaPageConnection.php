<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaPageConnection extends Model
{
    protected $fillable = [
        'page_id',
        'page_name',
        'category',
        'access_token',
        'token_expires_at',
        'connected_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }
}
