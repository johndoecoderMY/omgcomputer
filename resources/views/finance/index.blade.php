@extends('layouts.app')

@section('title', 'Company Funds')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Finance" title="Company funds"
        description="Track account balances, money in and out, transfers, and upcoming bills." />

    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please check the form details.</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100"><div class="card-body">
                <div class="small text-body-secondary">Total funds across accounts</div>
                <div class="fs-4 fw-semibold">RM {{ number_format($totalFunds, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100"><div class="card-body">
                <div class="small text-body-secondary">Money out this month</div>
                <div class="fs-4 fw-semibold">RM {{ number_format((float) $monthExpenses, 2) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100"><div class="card-body">
                <div class="small text-body-secondary">Bills due in the next 30 days</div>
                <div class="fs-4 fw-semibold {{ $overdueCount ? 'text-danger' : '' }}">
                    RM {{ number_format($dueAmount, 2) }}
                </div>
                <div class="small text-body-secondary">{{ $dueCount }} item(s), {{ $overdueCount }} overdue</div>
            </div></div>
        </div>
    </div>

    <div class="alert alert-info" role="note">
        Enter each account's real opening balance when you set it up. Existing bill payments are not assigned to an account; new bill payments will reduce the account you select. Record customer receipts as Money in transactions.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Add an account</h2></div>
                <form method="POST" action="{{ route('finance.accounts.store') }}">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="account-name">Account name</label>
                            <input class="form-control" id="account-name" name="name" value="{{ old('name') }}"
                                maxlength="120" placeholder="e.g. Main bank account" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="account-type">Account type</label>
                                <select class="form-select" id="account-type" name="type" required>
                                    @foreach (\App\Models\FinanceAccount::TYPES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', 'bank') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="opening-balance">Opening balance (RM)</label>
                                <input class="form-control" id="opening-balance" name="opening_balance" type="number"
                                    min="0" step="0.01" value="{{ old('opening_balance', '0.00') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="opening-balance-date">Balance as of</label>
                                <input class="form-control" id="opening-balance-date" name="opening_balance_date" type="date"
                                    value="{{ old('opening_balance_date', today()->toDateString()) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="account-notes">Notes <span class="text-body-secondary">(optional)</span></label>
                                <textarea class="form-control" id="account-notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-body text-end">
                        <button class="btn btn-primary" type="submit">Add account</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Record a transaction</h2></div>
                <form method="POST" action="{{ route('finance.transactions.store') }}">
                    @csrf
                    <div class="card-body">
                        @if ($activeAccounts->isEmpty())
                            <p class="text-body-secondary mb-0">Add an active account before recording a transaction.</p>
                        @else
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="transaction-type">Transaction type</label>
                                    <select class="form-select" id="transaction-type" name="type" required>
                                        @foreach (\App\Models\FinanceTransaction::TYPES as $value => $label)
                                            <option value="{{ $value }}" @selected(old('type', 'expense') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="transaction-account">Account</label>
                                    <select class="form-select" id="transaction-account" name="account_id" required>
                                        @foreach ($activeAccounts as $account)
                                            <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id)>
                                                {{ $account->name }} (RM {{ number_format($account->current_balance, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6" id="destination-account-field" hidden>
                                    <label class="form-label" for="destination-account">Transfer to</label>
                                    <select class="form-select" id="destination-account" name="destination_account_id">
                                        <option value="">Choose an account</option>
                                        @foreach ($activeAccounts as $account)
                                            <option value="{{ $account->id }}" @selected((string) old('destination_account_id') === (string) $account->id)>
                                                {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="transaction-amount">Amount (RM)</label>
                                    <input class="form-control" id="transaction-amount" name="amount" type="number"
                                        min="0.01" step="0.01" value="{{ old('amount') }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="transaction-description">Description</label>
                                    <input class="form-control" id="transaction-description" name="description"
                                        value="{{ old('description') }}" maxlength="255" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="transaction-date">Date</label>
                                    <input class="form-control" id="transaction-date" name="transaction_date" type="date"
                                        value="{{ old('transaction_date', today()->toDateString()) }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="transaction-reference">Reference <span class="text-body-secondary">(optional)</span></label>
                                    <input class="form-control" id="transaction-reference" name="reference"
                                        value="{{ old('reference') }}" maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="transaction-notes">Notes <span class="text-body-secondary">(optional)</span></label>
                                    <textarea class="form-control" id="transaction-notes" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        @endif
                    </div>
                    @if ($activeAccounts->isNotEmpty())
                        <div class="card-footer bg-body text-end">
                            <button class="btn btn-primary" type="submit">Save transaction</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0">Accounts</h2></div>
        <div class="card-body">
            @if ($accounts->isEmpty())
                <p class="text-body-secondary mb-0">No accounts yet. Add an account above to start tracking funds.</p>
            @else
                <div class="row g-3">
                    @foreach ($accounts as $account)
                        <div class="col-sm-6 col-xl-4">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $account->name }}</div>
                                        <div class="small text-body-secondary">{{ \App\Models\FinanceAccount::TYPES[$account->type] }}</div>
                                    </div>
                                    <span class="badge {{ $account->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $account->is_active ? 'Active' : 'Archived' }}
                                    </span>
                                </div>
                                <div class="fs-5 fw-semibold mt-3">RM {{ number_format($account->current_balance, 2) }}</div>
                                <div class="small text-body-secondary">
                                    Opening balance RM {{ number_format((float) $account->opening_balance, 2) }}
                                    as of {{ $account->opening_balance_date->format('M j, Y') }}
                                </div>
                                @if ($account->notes)<div class="small mt-2">{{ $account->notes }}</div>@endif
                                <form class="mt-3" method="POST" action="{{ route('finance.accounts.toggle', $account) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">
                                        {{ $account->is_active ? 'Archive account' : 'Activate account' }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Bills to pay</h2>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('expenses.index', 'bills') }}">Manage bills</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Bill</th><th>Due</th><th class="text-end">Remaining</th></tr></thead>
                        <tbody>
                            @forelse ($expensesDue as $expense)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $expense->name }}</div>
                                        <div class="small text-body-secondary">{{ \App\Models\BusinessExpense::CATEGORIES[$expense->category] }}</div>
                                    </td>
                                    <td class="{{ $expense->due_date->isBefore(today()) ? 'text-danger' : '' }}">
                                        {{ $expense->due_date->format('M j, Y') }}
                                    </td>
                                    <td class="text-end text-nowrap">RM {{ number_format($expense->remaining_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-4">No unpaid bills due in the next 30 days.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($dueCount > $expensesDue->count())
                    <div class="card-footer bg-body small text-body-secondary">Showing the next {{ $expensesDue->count() }} of {{ $dueCount }} bills.</div>
                @endif
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">Spending &amp; bills</h2></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    @foreach (\App\Models\BusinessExpense::CATEGORIES as $category => $label)
                        <a class="btn btn-outline-primary" href="{{ route('expenses.index', $category) }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="h5 mb-0">Recent account activity</h2></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Account</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="text-nowrap">{{ $transaction->transaction_date->format('M j, Y') }}</td>
                            <td>{{ \App\Models\FinanceTransaction::TYPES[$transaction->type] }}</td>
                            <td>
                                <div class="fw-semibold">{{ $transaction->description }}</div>
                                @if ($transaction->notes)<div class="small text-body-secondary">{{ $transaction->notes }}</div>@endif
                            </td>
                            <td>
                                @if ($transaction->type === 'transfer')
                                    {{ $transaction->account->name }} <span class="bi bi-arrow-right" aria-label="to"></span> {{ $transaction->destinationAccount->name }}
                                @else
                                    {{ $transaction->account->name }}
                                @endif
                            </td>
                            <td>{{ $transaction->reference ?: '—' }}</td>
                            <td class="text-end text-nowrap {{ $transaction->type === 'income' ? 'text-success' : ($transaction->type === 'expense' ? 'text-danger' : '') }}">
                                {{ $transaction->type === 'income' ? '+' : ($transaction->type === 'expense' ? '-' : '') }}
                                RM {{ number_format((float) $transaction->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">No transactions recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->count() === 50)
            <div class="card-footer bg-body small text-body-secondary">Showing the 50 most recent transactions.</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const type = document.getElementById('transaction-type');
            const destination = document.getElementById('destination-account-field');
            const destinationSelect = document.getElementById('destination-account');
            const account = document.getElementById('transaction-account');
            if (!type || !destination || !destinationSelect || !account) return;

            const updateTransferFields = () => {
                const isTransfer = type.value === 'transfer';
                destination.hidden = !isTransfer;
                destinationSelect.required = isTransfer;
                Array.from(destinationSelect.options).forEach(option => {
                    option.disabled = option.value !== '' && option.value === account.value;
                });
                if (destinationSelect.value === account.value) destinationSelect.value = '';
            };

            type.addEventListener('change', updateTransferFields);
            account.addEventListener('change', updateTransferFields);
            updateTransferFields();
        });
    </script>
@endpush
