<?php

namespace Tests\Feature;

use App\Models\CustomerMessageTemplate;
use App\Models\BillingDocument;
use App\Models\InventoryPart;
use App\Models\MarketingCampaign;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_report_uses_assigned_task_completion(): void
    {
        $user = $this->adminUser(['name' => 'Mina Chen']);
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
        $user = $this->adminUser();
        config([
            'services.parcel_tracking.api_key' => null,
        ]);
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
            ->assertSee('Marketing & WhatsApp')
            ->assertSee('Configuration presence does not confirm a live connection.')
            ->assertSee('META_WHATSAPP_PHONE_NUMBER_ID')
            ->assertSee('Not connected');
        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Delivery API readiness')
            ->assertSee('17TRACK')
            ->assertSee('17TRACK API docs')
            ->assertSee('TRACK17_API_KEY')
            ->assertSee('not set');
        $this->assertSame(1, MarketingCampaign::query()->count());
    }

    public function test_tiktok_marketing_section_shows_setup_presence_without_exposing_secrets(): void
    {
        $user = $this->adminUser();
        config([
            'services.tiktok.client_key' => 'test-tiktok-client-key',
            'services.tiktok.client_secret' => 'test-tiktok-client-secret',
            'services.tiktok.redirect_uri' => 'https://example.test/tiktok/callback',
        ]);

        $this->actingAs($user)->get(route('marketing.tiktok'))
            ->assertOk()
            ->assertSee('TikTok')
            ->assertSee('video.publish')
            ->assertSee('TIKTOK_CLIENT_KEY')
            ->assertSee('Configured')
            ->assertSee('Not available')
            ->assertDontSee('test-tiktok-client-key')
            ->assertDontSee('test-tiktok-client-secret')
            ->assertSee(route('marketing.tiktok'), false);
    }

    public function test_parcel_tracking_readiness_displays_presence_without_exposing_credentials(): void
    {
        config([
            'services.parcel_tracking.api_key' => 'test-tracking-secret',
        ]);

        $this->actingAs($this->adminUser())->get(route('reports.index'))
            ->assertOk()
            ->assertSee('17TRACK API key configured')
            ->assertDontSee('test-tracking-secret');
    }

    public function test_marketing_page_reports_meta_configuration_presence_without_exposing_secrets(): void
    {
        $user = $this->adminUser();
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

    public function test_report_tables_are_bounded_and_totals_cover_all_records(): void
    {
        $admin = $this->adminUser();

        foreach (range(1, 51) as $index) {
            InventoryPart::query()->create([
                'name' => 'Report part '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'cost_price' => 2,
                'selling_price' => 4,
                'stock_quantity' => 1,
                'reorder_level' => 2,
            ]);
            MarketingCampaign::query()->create([
                'name' => 'Report campaign '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'channel' => 'social',
                'status' => 'completed',
                'spend' => 1,
                'impressions' => 10,
                'clicks' => 2,
                'leads' => 1,
                'conversions' => 1,
                'attributed_revenue' => 2,
            ]);
            BillingDocument::query()->create([
                'number' => 'RPT-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'type' => 'invoice',
                'status' => 'issued',
                'user_id' => $admin->id,
                'customer_name' => 'Report customer',
                'issue_date' => today()->startOfMonth()->addDays(4)->toDateString(),
                'subtotal' => 10,
                'tax_rate' => 0,
                'tax_amount' => 0,
                'total' => 10,
                'paid_amount' => 0,
            ]);
        }
        User::factory()->count(51)->create();

        $period = [
            'from' => today()->startOfMonth()->toDateString(),
            'to' => today()->endOfMonth()->toDateString(),
        ];
        $this->actingAs($admin)->get(route('reports.sales', $period))
            ->assertOk()
            ->assertSee('Issued invoices')
            ->assertSee('page=2', false)
            ->assertSee('RM 510.00');
        $this->actingAs($admin)->get(route('reports.inventory'))
            ->assertOk()
            ->assertSee('51 tracked item(s)')
            ->assertSee('page=2', false)
            ->assertSee('RM 102.00')
            ->assertDontSee('Report part 51');
        $this->actingAs($admin)->get(route('reports.teams'))
            ->assertOk()
            ->assertSee('page=2', false)
            ->assertSee('Team performance');
        $this->actingAs($admin)->get(route('reports.marketing'))
            ->assertOk()
            ->assertSee('RM 51.00')
            ->assertSee('RM 102.00')
            ->assertSee('page=2', false)
            ->assertDontSee('Report campaign 51');
        $this->actingAs($admin)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Delivery API readiness');
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

    private function adminUser(array $attributes = []): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create($attributes);
        $admin->assignRole('admin');

        return $admin;
    }
}
