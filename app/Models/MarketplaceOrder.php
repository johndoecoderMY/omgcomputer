<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceOrder extends Model
{
    protected $fillable = [
        'marketplace_shop_id',
        'external_order_id',
        'status',
        'ordered_at',
        'currency',
        'total_amount',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'ordered_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'raw_payload' => 'array',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(MarketplaceShop::class, 'marketplace_shop_id');
    }
}
