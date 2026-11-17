<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceAccount extends Model
{
    public const TYPES = [
        'bank' => 'Bank account',
        'cash' => 'Cash',
        'e_wallet' => 'E-wallet',
        'other' => 'Other',
    ];

    protected $fillable = [
        'name',
        'type',
        'opening_balance',
        'opening_balance_date',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'opening_balance_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'account_id');
    }

    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'destination_account_id');
    }

    public function getCurrentBalanceAttribute(): float
    {
        $income = array_key_exists('income_total', $this->attributes)
            ? ($this->attributes['income_total'] ?? 0)
            : $this->transactions()->where('type', 'income')->sum('amount');
        $expense = array_key_exists('expense_total', $this->attributes)
            ? ($this->attributes['expense_total'] ?? 0)
            : $this->transactions()->where('type', 'expense')->sum('amount');
        $transferOut = array_key_exists('transfer_out_total', $this->attributes)
            ? ($this->attributes['transfer_out_total'] ?? 0)
            : $this->transactions()->where('type', 'transfer')->sum('amount');
        $transferIn = array_key_exists('transfer_in_total', $this->attributes)
            ? ($this->attributes['transfer_in_total'] ?? 0)
            : $this->incomingTransfers()->where('type', 'transfer')->sum('amount');

        return round(
            (float) $this->opening_balance
                + (float) $income
                - (float) $expense
                - (float) $transferOut
                + (float) $transferIn,
            2,
        );
    }
}
