<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransaction extends Model
{
    public const TYPES = [
        'income' => 'Money in',
        'expense' => 'Money out',
        'transfer' => 'Transfer',
    ];

    protected $fillable = [
        'account_id',
        'destination_account_id',
        'business_expense_payment_id',
        'user_id',
        'type',
        'description',
        'amount',
        'transaction_date',
        'reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'destination_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function businessExpensePayment(): BelongsTo
    {
        return $this->belongsTo(BusinessExpensePayment::class);
    }
}
