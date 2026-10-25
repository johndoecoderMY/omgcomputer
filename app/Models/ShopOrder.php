<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    public const STATUSES = [
        'pending_payment' => 'Pending payment',
        'paid' => 'Paid',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'uuid',
        'order_number',
        'customer_id',
        'user_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'status',
        'payment_status',
        'stock_reserved',
        'provider_purchase_id',
        'checkout_url',
        'tracking_number',
        'subtotal',
        'total',
        'currency',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'stock_reserved' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}
