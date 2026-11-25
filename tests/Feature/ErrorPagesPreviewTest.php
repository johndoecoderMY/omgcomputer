<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_customer_users_can_preview_error_pages_without_sidebar_links(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->get(route('error-pages.preview', '404'))
            ->assertNotFound()
            ->assertSee('Error page previews')
            ->assertSee('404 · Not found')
            ->assertSee('Page not found')
            ->assertSee('logoex2.png')
            ->assertSee('logoex2w.png')
            ->assertSee('brand-logo-light', false)
            ->assertSee('brand-logo-dark', false)
            ->assertSee('html[data-bs-theme="light"] .brand-logo-dark', false)
            ->assertSee('html[data-bs-theme="dark"] .brand-logo-light', false);

        $this->get(route('error-pages.preview', '403'))
            ->assertForbidden()
            ->assertSee('Access denied');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Error pages')
            ->assertDontSee('errorPagesMenu');
    }

    public function test_error_page_previews_require_authentication_and_only_allow_known_codes(): void
    {
        $this->get(route('error-pages.preview', '404'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('error-pages.preview', '999'))
            ->assertNotFound();
    }

    public function test_real_not_found_errors_do_not_show_the_preview_navigation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/route-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertDontSee('Error page previews');
    }

    public function test_error_pages_follow_the_selected_public_locale(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->withSession(['public_locale' => 'ms'])
            ->get(route('error-pages.preview', '404'))
            ->assertNotFound()
            ->assertSee('Halaman tidak dijumpai');

        $this->actingAs($admin)
            ->withSession(['public_locale' => 'zh_CN'])
            ->get(route('error-pages.preview', '403'))
            ->assertForbidden()
            ->assertSee('拒绝访问');
    }
}
