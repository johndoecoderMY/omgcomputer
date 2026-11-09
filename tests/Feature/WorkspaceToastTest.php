<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceToastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlannerRoleSeeder::class);
    }

    public function test_validation_errors_are_shown_as_workspace_toasts(): void
    {
        $this->actingAs(User::factory()->create());

        $this->from(route('projects.create'))
            ->followingRedirects()
            ->post(route('projects.store'), ['name' => ''])
            ->assertOk()
            ->assertSee('data-app-toast-container', false)
            ->assertSee('bottom-0 end-0', false)
            ->assertSee('--falcon-toast-bg: var(--falcon-emphasis-bg)', false)
            ->assertSee('--falcon-toast-header-bg: var(--falcon-emphasis-bg)', false)
            ->assertSee('@media (max-width: 575.98px)', false)
            ->assertSee('<strong class="me-auto">Project creation</strong>', false)
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
            ->assertSee('--falcon-toast-bg: var(--falcon-emphasis-bg)', false)
            ->assertSee('--falcon-toast-header-bg: var(--falcon-emphasis-bg)', false)
            ->assertSee('@media (max-width: 575.98px)', false)
            ->assertSee('<strong class="me-auto">Project creation</strong>', false)
            ->assertSee('Project created successfully.')
            ->assertDontSee('class="alert alert-success"', false)
            ->assertDontSee('class="alert alert-danger"', false)
            ->assertSee("document.querySelectorAll('.alert-success, .alert-danger')", false);
    }

    public function test_all_flash_statuses_share_the_current_operation_title(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->withSession([
            'success' => 'Created successfully.',
            'error' => 'Could not create.',
            'warning' => 'Review this project.',
            'info' => 'Project details are ready.',
        ])->get(route('projects.create'))
            ->assertOk()
            ->assertSee('Created successfully.')
            ->assertSee('Could not create.')
            ->assertSee('Review this project.')
            ->assertSee('Project details are ready.');

        $this->assertSame(4, substr_count($response->getContent(), '<strong class="me-auto">Project creation</strong>'));
    }

    public function test_public_pages_do_not_load_workspace_toasts(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Website design &amp; development', false)
            ->assertSee('href="#website-pricing"', false)
            ->assertSee('Discuss a website project')
            ->assertSee('website-pricing')
            ->assertSee('Website')
            ->assertSee('Contact Us')
            ->assertSee(route('website-previews.index'), false)
            ->assertSee('Starter landing page')
            ->assertSee('Business website')
            ->assertSee('Custom website')
            ->assertDontSee('<p class="h3 text-primary mb-3">Request a quote</p>', false)
            ->assertSee('href="#website-pricing"', false)
            ->assertSee('href="#contact"', false)
            ->assertDontSee('Packages')
            ->assertDontSee('Websites</a>', false)
            ->assertSee('Q&amp;A', false)
            ->assertSee('href="#faq"', false)
            ->assertSee('navbar navbar-expand-lg navbar-dark bg-dark fixed-top py-1', false)
            ->assertSee('height="48"', false)
            ->assertSee('btn btn-outline-light btn-sm rounded-pill px-3 py-1', false)
            ->assertSee(route('shop.public'), false)
            ->assertSee('#services', false)
            ->assertSee('website-design-illustration.svg')
            ->assertSee('Responsive business website shown on desktop and mobile screens')
            ->assertSee('hero-technician.jpg')
            ->assertSee('repair-board-work.jpg')
            ->assertSee('repair-pc-components.jpg')
            ->assertSee('website-design-work.jpg')
            ->assertSee('ready-built-pc.jpg')
            ->assertSee('PC diagnosis &amp; repair', false)
            ->assertSee('Upgrades &amp; software support', false)
            ->assertSee('Ready-built custom PCs')
            ->assertSee(route('shop.public', ['type' => 'ready_built_pc']), false)
            ->assertSee('Browse ready-built PCs')
            ->assertSee('Not sure what you need?')
            ->assertSee('About OMG Computer')
            ->assertSee('Common computer questions')
            ->assertSee('Ready for the next step?')
            ->assertSee('landing-contact')
            ->assertSee('contact-card')
            ->assertSee('Message us on WhatsApp')
            ->assertSee('PC repairs, upgrades, ready-built computers, and custom websites')
            ->assertSee('PC diagnosis &amp; repair', false)
            ->assertSee('Upgrades &amp; software support', false)
            ->assertSee('Ready-built custom PCs')
            ->assertSee(route('shop.public', ['type' => 'ready_built_pc']), false)
            ->assertSee('Website previews')
            ->assertSee(route('website-previews.index'), false)
            ->assertSee('Shop parts &amp; ready-built PCs', false)
            ->assertSee('id="help"', false)
            ->assertSee('id="how-it-works"', false)
            ->assertSee('id="about"', false)
            ->assertSee('id="faq"', false)
            ->assertSee('id="contact"', false)
            ->assertDontSee('id="booking"', false)
            ->assertDontSee('id="repair-tracking"', false)
            ->assertDontSee('data-app-toast-container', false)
            ->assertDontSee("document.querySelectorAll('.alert-success, .alert-danger')", false);

        $this->assertSame(6, substr_count($response->getContent(), '<section'));
        $this->assertSame(4, substr_count($response->getContent(), 'col-md-6 col-lg-3'));
        $this->assertSame(3, substr_count($response->getContent(), 'Request a quote</a>'));
    }
}
