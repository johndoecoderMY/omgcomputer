<?php

namespace Tests\Feature;

use App\Models\InventoryPart;
use App\Models\MarketingCampaign;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_report_uses_assigned_task_completion(): void
    {
        $user = User::factory()->create(['name' => 'Mina Chen']);
        $project = Project::query()->create(['name' => 'Support launch']);
        Task::query()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Prepare deployment',
            'status' => 'done',
            'priority' => 'high',
        ]);
        Task::query()->create([
            'project_id' => $project->id,
            'assigned_to' => $user->id,
            'title' => 'Review checklist',
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        $this->actingAs($user)->get(route('reports.teams'))
            ->assertOk()
            ->assertSee('Mina Chen')
            ->assertSee('50%')
            ->assertSee('Team performance');
    }

    public function test_inventory_and_marketing_reports_render_saved_data(): void
    {
        $user = User::factory()->create();
        InventoryPart::query()->create([
            'name' => 'USB-C adapter',
            'sku' => 'ADAPTER-01',
            'cost_price' => 4,
            'selling_price' => 12,
            'stock_quantity' => 2,
            'reorder_level' => 3,
        ]);

        $this->actingAs($user)->get(route('reports.inventory'))
            ->assertOk()
            ->assertSee('USB-C adapter')
            ->assertSee('At or below reorder level');

        $this->actingAs($user)->from(route('reports.marketing'))->post(route('reports.marketing.store'), [
            'name' => 'October service campaign',
            'channel' => 'email',
            'status' => 'completed',
            'spend' => 125,
            'impressions' => 2400,
            'clicks' => 180,
            'leads' => 24,
            'conversions' => 8,
            'attributed_revenue' => 960,
        ])->assertRedirect(route('reports.marketing'));

        $this->assertDatabaseHas('marketing_campaigns', ['name' => 'October service campaign']);
        $this->actingAs($user)->get(route('reports.marketing'))
            ->assertOk()
            ->assertSee('October service campaign')
            ->assertSee('RM 960.00');
        $this->assertSame(1, MarketingCampaign::query()->count());
    }

    public function test_products_page_shows_business_plan_offers_and_filters_by_category(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('products.index'))
            ->assertOk()
            ->assertSee('Laptop cleaning')
            ->assertSee('FYP consultation')
            ->assertSee('RM60–100');

        $this->actingAs($user)->get(route('products.index', ['category' => 'student']))
            ->assertOk()
            ->assertSee('FYP consultation')
            ->assertDontSee('Laptop cleaning');
    }
}