<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdsConnection extends Model
{
    protected $fillable = [
        'account_id',
        'account_name',
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
