<?php

namespace App\Http\Controllers;

use App\Models\BusinessExpense;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View
    {
        $accounts = FinanceAccount::query()
            ->withSum(['transactions as income_total' => fn (Builder $query) => $query->where('type', 'income')], 'amount')
            ->withSum(['transactions as expense_total' => fn (Builder $query) => $query->where('type', 'expense')], 'amount')
            ->withSum(['transactions as transfer_out_total' => fn (Builder $query) => $query->where('type', 'transfer')], 'amount')
            ->withSum([
                'incomingTransfers as transfer_in_total' => fn (Builder $query) => $query->where('type', 'transfer'),
            ], 'amount')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $transactions = FinanceTransaction::query()
            ->with(['account', 'destinationAccount', 'user'])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(50)
            ->get();

        $expensesDue = BusinessExpense::query()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today()->addDays(30))
            ->whereColumn('cycle_paid_amount', '<', 'amount')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $monthExpenses = FinanceTransaction::query()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [now()->startOfMonth()->toDateString(), today()->toDateString()])
            ->sum('amount');

        return view('finance.index', [
            'accounts' => $accounts,
            'activeAccounts' => $accounts->where('is_active', true)->values(),
            'transactions' => $transactions,
            'expensesDue' => $expensesDue,
            'monthExpenses' => $monthExpenses,
            'totalFunds' => $accounts->sum(fn (FinanceAccount $account): float => $account->current_balance),
            'dueAmount' => $expensesDue->sum(fn (BusinessExpense $expense): float => $expense->remaining_amount),
            'dueCount' => $expensesDue->count(),
            'overdueCount' => $expensesDue->filter(fn (BusinessExpense $expense): bool => $expense->due_date->isBefore(today()))->count(),
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(FinanceAccount::TYPES))],
            'opening_balance' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'opening_balance_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        FinanceAccount::query()->create($data);

        return redirect()->route('finance.index')->with('success', 'Account added.');
    }

    public function storeTransaction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(FinanceTransaction::TYPES))],
            'account_id' => ['required', 'integer', 'exists:finance_accounts,id'],
            'destination_account_id' => [
                Rule::requiredIf($request->input('type') === 'transfer'),
                'nullable',
                'integer',
                'different:account_id',
                'exists:finance_accounts,id',
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'description' => ['required', 'string', 'max:255'],
            'transaction_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $source = FinanceAccount::query()->whereKey($data['account_id'])->lockForUpdate()->firstOrFail();
            if (! $source->is_active) {
                throw ValidationException::withMessages([
                    'account_id' => 'Choose an active account.',
                ]);
            }

            if ($data['type'] === 'transfer') {
                $destination = FinanceAccount::query()
                    ->whereKey($data['destination_account_id'])
                    ->lockForUpdate()
                    ->firstOrFail();
                if (! $destination->is_active) {
                    throw ValidationException::withMessages([
                        'destination_account_id' => 'Choose an active destination account.',
                    ]);
                }
                if ($data['transaction_date'] < $destination->opening_balance_date->toDateString()) {
                    throw ValidationException::withMessages([
                        'transaction_date' => 'The transfer date cannot be before the destination account opening balance date.',
                    ]);
                }
            }

            if ($data['transaction_date'] < $source->opening_balance_date->toDateString()) {
                throw ValidationException::withMessages([
                    'transaction_date' => 'The transaction date cannot be before the account opening balance date.',
                ]);
            }

            FinanceTransaction::query()->create([
                ...$data,
                'destination_account_id' => $data['type'] === 'transfer' ? $data['destination_account_id'] : null,
                'user_id' => $request->user()->id,
            ]);
        });

        return redirect()->route('finance.index')->with('success', 'Transaction recorded.');
    }

    public function toggleAccount(FinanceAccount $account): RedirectResponse
    {
        $account->update(['is_active' => ! $account->is_active]);

        return redirect()->route('finance.index')->with(
            'success',
            $account->is_active ? 'Account activated.' : 'Account archived.',
        );
    }
}
