<?php

namespace Tests\Feature;

use App\Models\BusinessExpense;
use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessExpenseAndProjectLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_service_payments_advance_the_due_date_and_keep_history(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('expenses.store', 'services'), [
            'name' => 'SSM renewal',
            'vendor' => 'SSM',
            'amount' => 120,
            'frequency' => 'yearly',
            'due_date' => '2026-10-21',
        ])->assertRedirect(route('expenses.index', 'services'));

        $expense = BusinessExpense::query()->firstOrFail();
        $this->post(route('expenses.payments.store', ['services', $expense]), [
            'amount' => 50,
            'paid_at' => '2026-10-20',
            'reference' => 'Transfer 001',
        ])->assertRedirect(route('expenses.index', 'services'));

        $this->assertSame('50.00', $expense->fresh()->cycle_paid_amount);
        $this->assertSame('2026-10-21', $expense->fresh()->due_date->toDateString());

        $this->post(route('expenses.payments.store', ['services', $expense]), [
            'amount' => 70,
            'paid_at' => '2026-10-21',
        ])->assertRedirect(route('expenses.index', 'services'));

        $expense->refresh();
        $this->assertSame('0.00', $expense->cycle_paid_amount);
        $this->assertSame('2027-10-21', $expense->due_date->toDateString());
        $this->assertDatabaseCount('business_expense_payments', 2);
        $this->get(route('expenses.index', 'services'))
            ->assertOk()
            ->assertSee('SSM renewal')
            ->assertSee('2 payment(s)');
    }

    public function test_expenses_are_category_scoped_and_payments_cannot_exceed_the_remaining_balance(): void
    {
        $this->actingAs(User::factory()->create());
        $expense = BusinessExpense::query()->create([
            'category' => 'bills',
            'name' => 'Electricity',
            'amount' => 100,
            'frequency' => 'once',
            'due_date' => today()->subDays(5),
        ]);

        $this->get(route('expenses.edit', ['rental', $expense]))->assertNotFound();
        $this->post(route('expenses.payments.store', ['bills', $expense]), [
            'amount' => 100.01,
            'paid_at' => today()->toDateString(),
        ])->assertSessionHasErrors('amount');
        $this->post(route('expenses.payments.store', ['bills', $expense]), [
            'amount' => 50,
            'paid_at' => today()->toDateString(),
        ])->assertRedirect(route('expenses.index', 'bills'));
        $this->assertDatabaseCount('business_expense_payments', 1);
        $this->get(route('expenses.index', 'bills'))
            ->assertOk()
            ->assertSee('Overdue')
            ->assertSee('RM 50.00 paid');
    }

    public function test_customers_can_only_see_project_links_marked_as_customer_visible(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $project = Project::query()->create(['name' => 'Customer project']);
        Customer::query()->create([
            'project_id' => $project->id,
            'name' => 'Client',
            'email' => 'client@example.test',
        ]);
        $project->links()->create([
            'label' => 'Public staging site',
            'url' => 'https://staging.example.test',
            'visible_to_customer' => true,
        ]);
        $project->links()->create([
            'label' => 'Private GitHub repository',
            'url' => 'https://github.com/example/private',
            'visible_to_customer' => false,
        ]);
        $customer = User::factory()->create(['email' => 'client@example.test']);
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Public staging site')
            ->assertSee('https://staging.example.test')
            ->assertDontSee('Private GitHub repository')
            ->assertDontSee('github.com/example/private');
        $this->post(route('projects.links.store', $project), [
            'label' => 'Customer edit attempt',
            'url' => 'https://example.test',
        ])->assertForbidden();
    }

    public function test_project_links_validate_safe_urls_and_are_scoped_to_their_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Project A']);
        $otherProject = Project::query()->create(['name' => 'Project B']);

        $this->post(route('projects.links.store', $project), [
            'label' => 'GitHub',
            'url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('url');
        $this->post(route('projects.links.store', $project), [
            'label' => 'GitHub repository',
            'url' => 'https://github.com/example/repo',
            'visible_to_customer' => '1',
        ])->assertRedirect(route('projects.show', $project));

        $link = $project->links()->firstOrFail();
        $this->put(route('projects.links.update', [$otherProject, $link->id]), [
            'label' => 'Tampered link',
            'url' => 'https://example.test',
        ])->assertNotFound();
        $this->assertDatabaseHas('project_links', [
            'id' => $link->id,
            'label' => 'GitHub repository',
            'visible_to_customer' => true,
        ]);
    }

    public function test_customers_cannot_read_staff_only_project_or_expense_photos(): void
    {
        Storage::fake('local');
        $this->seed(PlannerRoleSeeder::class);
        $project = Project::query()->create(['name' => 'Private project']);
        Customer::query()->create([
            'project_id' => $project->id,
            'name' => 'Client',
            'email' => 'client@example.test',
        ]);
        $projectAttachment = $project->attachments()->create([
            'path' => 'attachments/projects/project.png',
            'original_name' => 'project.png',
            'mime_type' => 'image/png',
            'size' => 5,
        ]);
        $expense = BusinessExpense::query()->create([
            'category' => 'services',
            'name' => 'Private renewal',
            'amount' => 40,
            'frequency' => 'yearly',
            'due_date' => today()->addMonth(),
        ]);
        $expenseAttachment = $expense->attachments()->create([
            'path' => 'attachments/business_expenses/receipt.png',
            'original_name' => 'receipt.png',
            'mime_type' => 'image/png',
            'size' => 5,
        ]);
        Storage::disk('local')->put('attachments/projects/project.png', 'image');
        Storage::disk('local')->put('attachments/business_expenses/receipt.png', 'image');
        $customer = User::factory()->create(['email' => 'client@example.test']);
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->get(route('attachments.show', $projectAttachment))
            ->assertNotFound();
        $this->get(route('attachments.show', $expenseAttachment))->assertNotFound();
    }
}
