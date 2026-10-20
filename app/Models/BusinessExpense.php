<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BusinessExpense extends Model
{
    public const CATEGORIES = [
        'rental' => 'Rental',
        'bills' => 'Bills',
        'services' => 'Services',
    ];

    public const FREQUENCIES = [
        'once' => 'One-time',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ];

    protected $fillable = [
        'category',
        'name',
        'vendor',
        'amount',
        'frequency',
        'due_date',
        'cycle_paid_amount',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cycle_paid_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BusinessExpensePayment::class)->latest('paid_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, round((float) $this->amount - (float) $this->cycle_paid_amount, 2));
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->remaining_amount <= 0) {
            return 'paid';
        }

        if ($this->due_date?->isPast() && ! $this->due_date->isToday()) {
            return 'overdue';
        }

        if ((float) $this->cycle_paid_amount > 0) {
            return 'partially paid';
        }

        return 'due';
    }
}
