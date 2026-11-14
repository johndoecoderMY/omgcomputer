<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TikTokConnection extends Model
{
    protected $table = 'tiktok_connections';

    protected $fillable = [
        'open_id',
        'access_token',
        'refresh_token',
        'scope',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'connected_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
        ];
    }
}
