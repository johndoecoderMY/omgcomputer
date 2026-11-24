<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantDocumentChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_a_home_business_checklist_and_non_admins_cannot(): void
    {
        $this->get(route('admin.merchant-document-checklist'))->assertRedirect(route('login'));

        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.merchant-document-checklist'));

        $response
            ->assertOk()
            ->assertSee('Merchant document checklist')
            ->assertSee('Home-based business')
            ->assertSee('Front door photo')
            ->assertSee('Dedicated workspace photo')
            ->assertSee('Home/building exterior photos')
            ->assertSee('Product or inventory photos')
            ->assertSee('E-wallet sales evidence')
            ->assertSee('Foreign card use case')
            ->assertSee(route('refund-policy'), false)
            ->assertSee(route('privacy'), false)
            ->assertSee(route('shipping-policy'), false)
            ->assertSee(route('admin.company-profile.edit'), false)
            ->assertSee('Progress is saved in this browser only.')
            ->assertDontSee('type="file"', false);

        $sidebar = substr(
            $response->getContent(),
            strpos($response->getContent(), 'id="navbarVerticalNav"'),
        );
        $selfTestPosition = strpos($sidebar, route('self-test.index'));
        $merchantDocumentPosition = strpos($sidebar, route('admin.merchant-document-checklist'));

        $this->assertIsInt($selfTestPosition);
        $this->assertIsInt($merchantDocumentPosition);
        $this->assertTrue($selfTestPosition < $merchantDocumentPosition);

        $staff = User::factory()->create();
        $this->actingAs($staff)->get(route('admin.merchant-document-checklist'))->assertForbidden();
    }
}
