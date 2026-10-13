<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingDocument extends Model
{
    protected $fillable = [
        'user_id',
        'document_number',
        'type',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'issued_at',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'stock_committed_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_date' => 'date',
            'stock_committed_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingDocumentItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class);
    }

    public function getPaidTotalAttribute(): float
    {
        return (float) ($this->payments_sum_amount ?? $this->payments()->sum('amount'));
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, round((float) $this->total - $this->paid_total, 2));
    }
}
