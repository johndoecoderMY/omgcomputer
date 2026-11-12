<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class BillingPayment extends Model
{
    protected $fillable = [
        'billing_document_id', 'user_id', 'amount', 'method', 'reference', 'paid_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class, 'billing_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function receiptNumber(): Attribute
    {
        return Attribute::get(fn (): string => sprintf(
            'RCT-%s-%06d',
            $this->paid_at->format('Y'),
            $this->id,
        ));
    }
}