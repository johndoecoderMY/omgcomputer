<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceShop extends Model
{
    protected $fillable = [
        'platform',
        'external_shop_id',
        'shop_name',
        'region',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'refresh_token_expires_at',
        'last_order_import_at',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'access_token_expires_at' => 'datetime',
            'refresh_token_expires_at' => 'datetime',
            'last_order_import_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class);
    }
}
