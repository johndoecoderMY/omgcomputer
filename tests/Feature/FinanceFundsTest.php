<?php

namespace Tests\Feature;

use App\Models\BusinessExpense;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFundsTest extends TestCase
{
    use RefreshDatabase;

    public function test_accounts_income_expenses_and_transfers_update_account_balances(): void
    {
        $this->actingAs($this->adminUser());
        $this->post(route('finance.accounts.store'), [
            'name' => 'Main bank',
            'type' => 'bank',
            'opening_balance' => 1000,
            'opening_balance_date' => today()->toDateString(),
        ])->assertRedirect(route('finance.index'));
        $this->post(route('finance.accounts.store'), [
            'name' => 'Cash drawer',
            'type' => 'cash',
            'opening_balance' => 250,
            'opening_balance_date' => today()->toDateString(),
        ])->assertRedirect(route('finance.index'));

        $bank = FinanceAccount::query()->where('name', 'Main bank')->firstOrFail();
        $cash = FinanceAccount::query()->where('name', 'Cash drawer')->firstOrFail();
        $this->post(route('finance.transactions.store'), [
            'type' => 'income',
            'account_id' => $bank->id,
            'amount' => 500,
            'description' => 'Cash deposit',
            'transaction_date' => today()->toDateString(),
        ])->assertRedirect(route('finance.index'));
        $this->post(route('finance.transactions.store'), [
            'type' => 'expense',
            'account_id' => $bank->id,
            'amount' => 125,
            'description' => 'Office supplies',
            'transaction_date' => today()->toDateString(),
        ])->assertRedirect(route('finance.index'));
        $this->post(route('finance.transactions.store'), [
            'type' => 'transfer',
            'account_id' => $bank->id,
            'destination_account_id' => $cash->id,
            'amount' => 200,
            'description' => 'Cash withdrawal',
            'transaction_date' => today()->toDateString(),
        ])->assertRedirect(route('finance.index'));

        $this->assertEquals(1175, $bank->fresh()->current_balance);
        $this->assertEquals(450, $cash->fresh()->current_balance);
        $this->assertEquals(1625, FinanceAccount::query()
            ->withSum(['transactions as income_total' => fn ($query) => $query->where('type', 'income')], 'amount')
            ->withSum(['transactions as expense_total' => fn ($query) => $query->where('type', 'expense')], 'amount')
            ->withSum(['transactions as transfer_out_total' => fn ($query) => $query->where('type', 'transfer')], 'amount')
            ->withSum(['incomingTransfers as transfer_in_total' => fn ($query) => $query->where('type', 'transfer')], 'amount')
            ->get()
            ->sum(fn (FinanceAccount $account): float => $account->current_balance));

        $this->get(route('finance.index'))
            ->assertOk()
            ->assertSee('Company funds')
            ->assertSee('RM 1,625.00')
            ->assertSee('Office supplies')
            ->assertSee('Cash withdrawal');
    }

    public function test_bill_payments_are_linked_to_and_deducted_from_the_selected_account(): void
    {
        $this->actingAs($this->adminUser());
        $account = FinanceAccount::query()->create([
            'name' => 'Operating bank',
            'type' => 'bank',
            'opening_balance' => 800,
            'opening_balance_date' => today(),
        ]);
        $expense = BusinessExpense::query()->create([
            'category' => 'bills',
            'name' => 'Electricity',
            'amount' => 180,
            'frequency' => 'once',
            'due_date' => today(),
        ]);

        $this->post(route('expenses.payments.store', ['bills', $expense]), [
            'amount' => 80,
            'paid_at' => today()->toDateString(),
            'account_id' => $account->id,
        ])->assertRedirect(route('expenses.index', 'bills'));

        $transaction = FinanceTransaction::query()->firstOrFail();
        $this->assertSame('expense', $transaction->type);
        $this->assertSame('Bill payment: Electricity', $transaction->description);
        $this->assertEquals(800 - 80, $account->fresh()->current_balance);
        $this->assertSame($expense->payments()->firstOrFail()->id, $transaction->business_expense_payment_id);
        $this->get(route('expenses.index', 'bills'))->assertOk()->assertSee('Operating bank');
    }

    public function test_finance_is_restricted_to_administrators_and_inactive_accounts_cannot_be_used(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $member = User::factory()->create();
        $member->assignRole('customer');
        $this->actingAs($member)->get(route('finance.index'))->assertForbidden();

        $admin = $this->adminUser();
        $account = FinanceAccount::query()->create([
            'name' => 'Archived account',
            'type' => 'bank',
            'opening_balance' => 0,
            'opening_balance_date' => today(),
            'is_active' => false,
        ]);
        $this->actingAs($admin)->post(route('finance.transactions.store'), [
            'type' => 'income',
            'account_id' => $account->id,
            'amount' => 25,
            'description' => 'Invalid transaction',
            'transaction_date' => today()->toDateString(),
        ])->assertSessionHasErrors('account_id');
        $this->assertDatabaseCount('finance_transactions', 0);
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
