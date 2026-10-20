<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessExpensePayment extends Model
{
    protected $fillable = [
        'business_expense_id',
        'user_id',
        'paid_at',
        'period_due_date',
        'amount',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'period_due_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(BusinessExpense::class, 'business_expense_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
