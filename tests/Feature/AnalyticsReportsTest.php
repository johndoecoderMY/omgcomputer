<?php

namespace Tests\Feature;

use App\Models\CustomerMessageTemplate;
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
            ->assertSee('RM 960.00')
            ->assertSee('Marketing &WhatsApp', false)
            ->assertSee('Meta API is not connected yet.')
            ->assertSee('META_WHATSAPP_PHONE_NUMBER_ID')
            ->assertSee('Not connected');
        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Delivery API readiness')
            ->assertSee('EasyParcel Malaysia')
            ->assertSee('EasyParcel API docs')
            ->assertSee('EASYPARCEL_API_KEY')
            ->assertSee('not set');
        $this->assertSame(1, MarketingCampaign::query()->count());
    }

    public function test_delivery_api_readiness_displays_presence_without_exposing_credentials(): void
    {
        config([
            'services.delivery.provider' => 'easyparcel',
            'services.delivery.api_key' => 'test-delivery-secret',
            'services.delivery.api_url' => 'https://api.example.test',
            'services.delivery.environment' => 'sandbox',
        ]);

        $this->actingAs(User::factory()->create())->get(route('reports.index'))
            ->assertOk()
            ->assertSee('API key configured')
            ->assertSee('API URL configured')
            ->assertSee('Environment: Sandbox')
            ->assertDontSee('test-delivery-secret')
            ->assertDontSee('https://api.example.test');
    }

    public function test_marketing_page_reports_meta_configuration_presence_without_exposing_secrets(): void
    {
        $user = User::factory()->create();
        CustomerMessageTemplate::query()->create([
            'slug' => 'whatsapp-ready',
            'name' => 'WhatsApp ready',
            'stage' => 'ready',
            'channel' => 'whatsapp',
            'body' => 'Your device is ready.',
            'is_active' => true,
        ]);
        config([
            'services.meta.app_id' => 'app-id-test',
            'services.meta.app_secret' => 'test-meta-secret',
            'services.meta.access_token' => null,
            'services.meta.ad_account_id' => 'act_123',
            'services.meta.whatsapp_business_account_id' => null,
            'services.meta.whatsapp_phone_number_id' => 'phone-id-test',
            'services.meta.webhook_verify_token' => null,
        ]);

        $this->actingAs($user)->get(route('reports.marketing'))
            ->assertOk()
            ->assertSee('3 / 6')
            ->assertSee('3 / 4')
            ->assertSee('1 active WhatsApp message template')
            ->assertSee('Configured')
            ->assertDontSee('test-meta-secret');
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
