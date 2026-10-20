<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceToastTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_errors_are_shown_as_workspace_toasts(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('projects.create'))
            ->followingRedirects()
            ->post(route('projects.store'), ['name' => ''])
            ->assertOk()
            ->assertSee('data-app-toast-container', false)
            ->assertSee('bottom-0 end-0', false)
            ->assertSee('@media (max-width: 575.98px)', false)
            ->assertSee('Please check your input')
            ->assertSee('The name field is required.')
            ->assertSee("document.querySelectorAll('.alert-success, .alert-danger')", false);
    }

    public function test_success_messages_are_rendered_as_toasts_instead_of_inline_alerts(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('projects.store'), ['name' => 'Toast project'])
            ->assertRedirect();

        $project = Project::query()->where('name', 'Toast project')->firstOrFail();

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-app-toast-container', false)
            ->assertSee('bottom-0 end-0', false)
            ->assertSee('@media (max-width: 575.98px)', false)
            ->assertSee('Project created successfully.')
            ->assertSee("document.querySelectorAll('.alert-success, .alert-danger')", false);
    }

    public function test_public_pages_do_not_load_workspace_toasts(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-app-toast-container', false)
            ->assertDontSee("document.querySelectorAll('.alert-success, .alert-danger')", false);
    }
}
