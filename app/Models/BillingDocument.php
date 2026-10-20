<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingDocument extends Model
{
    protected $fillable = [
        'number', 'type', 'status', 'user_id', 'customer_name', 'customer_email',
        'repair_ticket_id', 'project_id', 'customer_phone', 'customer_address', 'issue_date', 'due_date', 'subtotal',
        'tax_rate', 'tax_amount', 'total', 'paid_amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingDocumentItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function repairTicket(): BelongsTo
    {
        return $this->belongsTo(RepairTicket::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getBalanceAttribute(): float
    {
        return max(0, round((float) $this->total - (float) $this->paid_amount, 2));
    }

    public function getDocumentNumberAttribute(): string
    {
        return $this->number;
    }

    public function getBalanceDueAttribute(): float
    {
        return $this->balance;
    }
}
