<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_customer_users_can_preview_error_pages_from_the_error_sidebar(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('error-pages.preview', '404'))
            ->assertOk()
            ->assertSee('Error page previews')
            ->assertSee('404 · Not found')
            ->assertSee('Page not found');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="errorPagesMenu"', false)
            ->assertSee(route('error-pages.preview', '403'))
            ->assertSee(route('error-pages.preview', '503'));
    }

    public function test_error_page_previews_require_authentication_and_only_allow_known_codes(): void
    {
        $this->get(route('error-pages.preview', '404'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('error-pages.preview', '999'))
            ->assertNotFound();
    }
}
