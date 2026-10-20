<?php

namespace Tests\Feature;

use App\Models\FlowChart;
use App\Models\Project;
use App\Models\ProjectTechnology;
use App\Models\Task;
use App\Models\User;
use App\Support\FlowChartPngRenderer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProjectDetailsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_page_header_renders_a_single_action_without_an_ellipsis(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Add customer')
            ->assertDontSee('aria-label="Page actions"', false)
            ->assertSee('page-header-action-single', false)
            ->assertSee('btn btn-primary', false);
    }

    public function test_project_list_shows_the_new_project_button_without_an_ellipsis(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('New project')
            ->assertDontSee('aria-label="Page actions"', false)
            ->assertSee('page-header-action-single', false)
            ->assertSee('btn btn-primary', false);
    }

    public function test_page_header_breadcrumbs_follow_sidebar_sections(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Support Desk')
            ->assertSee('Tickets');
        $this->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('Bookings')
            ->assertSee('Customer bookings');
        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Customers')
            ->assertSee('Customer records');
        $this->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Manage')
            ->assertSee('Parts & Inventory');
        $this->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reports & Analytics');
    }

    public function test_project_technology_stack_can_be_created_updated_and_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Mobile repair booking']);

        $this->post(route('projects.technology-stack.store', $project), [
            'name' => 'PHP',
            'version' => '8.3',
            'notes' => 'Backend runtime',
        ])->assertRedirect(route('projects.show', $project));

        $technology = ProjectTechnology::query()->firstOrFail();
        $this->assertSame($project->id, $technology->project_id);

        $this->put(route('projects.technology-stack.update', [$project, $technology]), [
            'name' => 'FullCalendar',
            'version' => '6.1.19',
            'notes' => 'Booking calendar',
        ])->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('project_technologies', [
            'id' => $technology->id,
            'name' => 'FullCalendar',
            'version' => '6.1.19',
        ]);

        $this->delete(route('projects.technology-stack.destroy', [$project, $technology]))
            ->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseMissing('project_technologies', ['id' => $technology->id]);
    }

    public function test_project_requirements_can_be_saved_listed_and_exported(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('projects.store'), [
            'name' => 'Repair booking portal',
            'requirements_notes' => 'Customer must be able to book online.',
            'requirements' => [
                ['requirement' => 'Allow customers to select a repair slot.'],
                ['requirement' => 'Send a booking confirmation.', 'is_completed' => '1'],
            ],
        ])->assertSessionHasNoErrors();

        $project = Project::query()->where('name', 'Repair booking portal')->firstOrFail();
        $requirements = $project->requirements()->orderBy('sort_order')->get();
        $this->assertCount(2, $requirements);
        $this->assertTrue($requirements[1]->is_completed);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Project requirements')
            ->assertSee('Requirements checklist and notes')
            ->assertSee('Customer must be able to book online.')
            ->assertSee('Allow customers to select a repair slot.')
            ->assertSee('Send a booking confirmation.');

        $this->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSee('Requirements checklist')
            ->assertSee('Allow customers to select a repair slot.');

        $this->put(route('projects.update', $project), [
            'name' => $project->name,
            'requirements_notes' => 'Updated scope notes.',
            'requirements' => [
                ['id' => $requirements[0]->id, 'requirement' => 'Support online repair bookings.', 'is_completed' => '1'],
                ['requirement' => 'Notify customers by email.'],
            ],
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'requirements_notes' => 'Updated scope notes.',
        ]);
        $this->assertDatabaseHas('project_requirements', [
            'id' => $requirements[0]->id,
            'requirement' => 'Support online repair bookings.',
            'is_completed' => true,
        ]);
        $this->assertDatabaseMissing('project_requirements', ['id' => $requirements[1]->id]);
        $this->assertDatabaseHas('project_requirements', [
            'project_id' => $project->id,
            'requirement' => 'Notify customers by email.',
        ]);

        $project->requirements_notes = 'Updated scope notes.';
        $project->setRelation('requirements', $project->requirements()->orderBy('sort_order')->get());
        $pdfHtml = view('projects.pdf', [
            'project' => $project,
            'sections' => ['requirements'],
            'flowCharts' => collect(),
        ])->render();
        $this->assertStringContainsString('Updated scope notes.', $pdfHtml);
        $this->assertStringContainsString('Support online repair bookings.', $pdfHtml);
        $this->assertStringContainsString('Notify customers by email.', $pdfHtml);

        $this->post(route('projects.export', $project), [
            'sections' => ['requirements'],
            'flowchart_mode' => 'all',
        ])->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_project_requirements_cannot_be_reassigned_from_another_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Original project']);
        $otherProject = Project::query()->create(['name' => 'Other project']);
        $requirement = $otherProject->requirements()->create([
            'requirement' => 'Keep this requirement on its project.',
        ]);

        $this->put(route('projects.update', $project), [
            'name' => $project->name,
            'requirements' => [
                ['id' => $requirement->id, 'requirement' => 'Attempted reassignment.'],
            ],
        ])->assertSessionHasErrors('requirements');

        $this->assertDatabaseHas('project_requirements', [
            'id' => $requirement->id,
            'project_id' => $otherProject->id,
            'requirement' => 'Keep this requirement on its project.',
        ]);
    }

    public function test_project_pdf_can_include_selected_sections_and_linked_flowcharts_but_not_tasks_by_default(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::query()->create([
            'name' => 'Device booking portal',
            'description' => 'A web portal for repair appointments.',
        ]);
        $project->technologyStack()->create([
            'name' => 'PHP',
            'version' => '8.3',
            'notes' => 'Backend runtime',
        ]);
        $project->technologyStack()->create([
            'name' => 'FullCalendar',
            'version' => '6.1.19',
            'notes' => 'Booking calendar',
        ]);
        $project->tasks()->create([
            'title' => 'Secret internal task title',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        $project->events()->create([
            'title' => 'Requirements review',
            'event_type' => 'progress_review',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
        ]);
        $chart = FlowChart::query()->create([
            'title' => 'Booking process flow',
            'description' => 'Public repair booking journey.',
            'category' => 'process',
            'is_example' => false,
            'steps' => [[
                'id' => (string) Str::uuid(),
                'label' => 'Select appointment',
                'kind' => 'action',
                'connections' => [],
            ]],
        ]);
        $project->flowCharts()->attach($chart);
        $secondChart = FlowChart::query()->create([
            'title' => 'Repair completion flow',
            'category' => 'process',
            'is_example' => false,
            'steps' => [
                [
                    'id' => 'start',
                    'label' => 'Start repair',
                    'kind' => 'start',
                    'x' => 0,
                    'y' => 0,
                    'connections' => ['finish'],
                ],
                [
                    'id' => 'finish',
                    'label' => 'Complete repair',
                    'kind' => 'end',
                    'x' => 1800,
                    'y' => 1400,
                    'connections' => [],
                ],
            ],
        ]);
        $project->flowCharts()->attach($secondChart);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $response = $this->post(route('projects.export', $project), [
            'sections' => ['overview', 'technology_stack', 'schedule', 'flowcharts'],
            'flowchart_mode' => 'selected',
            'flow_chart_ids' => [$chart->id, $secondChart->id],
        ]);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        preg_match_all('/\/Type\s*\/Page\b/', $response->getContent(), $pageMatches);
        $this->assertCount(3, $pageMatches[0], 'Project details followed by two flow charts should have no empty pages.');
        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains(strtolower($sql), 'from "tasks"')));

        $flowChartOnlyResponse = $this->post(route('projects.export', $project), [
            'sections' => ['flowcharts'],
            'flowchart_mode' => 'all',
        ]);
        $flowChartOnlyResponse->assertOk();
        preg_match_all('/\/Type\s*\/Page\b/', $flowChartOnlyResponse->getContent(), $flowChartOnlyPages);
        $this->assertCount(2, $flowChartOnlyPages[0], 'Each flow chart should use one page, with no blank pages between or after them.');

        $flowCharts = FlowChart::query()->whereIn('id', [$chart->id, $secondChart->id])->get();
        $flowCharts->each(fn (FlowChart $flowChart) => $flowChart->makeVisible('steps'));
        $flowChartImages = $flowCharts->mapWithKeys(fn (FlowChart $flowChart): array => [
            $flowChart->id => app(FlowChartPngRenderer::class)->render($flowChart),
        ]);
        $pdfHtml = view('projects.pdf', [
            'project' => $project,
            'sections' => ['flowcharts'],
            'flowCharts' => $flowCharts,
            'flowChartImages' => $flowChartImages,
        ])->render();
        $this->assertSame(2, substr_count($pdfHtml, 'class="flow-chart-page"'));
        $this->assertStringContainsString('<pagebreak />', $pdfHtml);
        $this->assertSame(2, substr_count($pdfHtml, 'src="data:image/png;base64,'));
        $this->assertStringContainsString('Flow chart: Booking process flow', $pdfHtml);
        $this->assertStringNotContainsString('<svg', $pdfHtml);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('aria-label="Page actions"', false)
            ->assertSee('page-header-action-dropdown', false)
            ->assertSee('All projects')
            ->assertSee('Task Gantt chart')
            ->assertSee('Project technology stack')
            ->assertSee('PHP')
            ->assertSee('8.3')
            ->assertSee('FullCalendar')
            ->assertSee('Secret internal task title');
    }

    public function test_flow_chart_pdf_images_wrap_long_labels_without_clipping(): void
    {
        $flowChart = FlowChart::query()->create([
            'title' => 'Long flow chart labels',
            'category' => 'service',
            'steps' => [
                [
                    'id' => 'start',
                    'label' => 'Identifies a goal or current challenge for the project',
                    'kind' => 'start',
                    'connections' => [],
                ],
            ],
        ]);
        $shortFlowChart = FlowChart::query()->create([
            'title' => 'Short flow chart labels',
            'category' => 'service',
            'steps' => [[
                'id' => 'start',
                'label' => 'Start',
                'kind' => 'start',
                'connections' => [],
            ]],
        ]);

        $renderer = app(FlowChartPngRenderer::class);
        $image = $renderer->render($flowChart);
        $this->assertStringStartsWith('data:image/png;base64,', $image['src']);
        $png = base64_decode(substr($image['src'], strlen('data:image/png;base64,')), true);
        $this->assertNotFalse($png);
        $imageSize = getimagesizefromstring($png);
        $this->assertIsArray($imageSize);
        $this->assertSame(IMAGETYPE_PNG, $imageSize[2]);

        $shortImage = $renderer->render($shortFlowChart);
        $shortPng = base64_decode(substr($shortImage['src'], strlen('data:image/png;base64,')), true);
        $shortImageSize = getimagesizefromstring($shortPng);
        $this->assertGreaterThan($shortImageSize[1], $imageSize[1]);
        $this->assertLessThanOrEqual(185, $image['height']);
    }

    public function test_project_pdf_can_include_a_task_gantt_chart_when_selected(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Gantt export project']);
        $project->tasks()->create([
            'title' => 'Exportable scheduled task',
            'status' => 'in_progress',
            'priority' => 'high',
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-05',
        ]);
        $project->tasks()->create([
            'title' => 'Completed scheduled task',
            'status' => 'done',
            'priority' => 'medium',
            'start_date' => '2026-11-06',
            'due_date' => '2026-11-06',
        ]);
        $project->tasks()->create([
            'title' => 'Backlog scheduled task',
            'status' => 'backlog',
            'priority' => 'low',
            'start_date' => '2026-11-07',
            'due_date' => '2026-11-07',
        ]);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });
        $response = $this->post(route('projects.export', $project), [
            'sections' => ['gantt'],
            'flowchart_mode' => 'all',
        ]);

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        preg_match_all('/\/Type\s*\/Page\b/', $response->getContent(), $ganttPages);
        $this->assertCount(1, $ganttPages[0], 'A Gantt-only export should not include a blank leading or trailing page.');
        $this->assertTrue(collect($queries)->contains(fn (string $sql): bool => str_contains(strtolower($sql), 'tasks')));
        $project->load('tasks.subtasks');
        $flowCharts = collect();
        $pdfHtml = view('projects.pdf', [
            'project' => $project,
            'sections' => ['gantt'],
            'flowCharts' => $flowCharts,
            'flowChartImages' => collect(),
        ])->render();
        $this->assertStringContainsString('Exportable scheduled task', $pdfHtml);
        $this->assertStringContainsString('Nov 1, 2026', $pdfHtml);
        $this->assertStringContainsString('class="project-gantt-chart"', $pdfHtml);
        $this->assertStringContainsString('.project-gantt-chart { display: block; margin: 0 auto; }', $pdfHtml);
        $this->assertStringContainsString('.flow-chart-diagram { display: block; margin: 8pt auto 0; }', $pdfHtml);
        $this->assertStringContainsString('<div class="gantt-section">', $pdfHtml);
        $this->assertStringNotContainsString('page-break-before: always;', $pdfHtml);
        $this->assertStringContainsString('<rect', $pdfHtml);
        $this->assertStringContainsString('Schedule', $pdfHtml);
        $this->assertStringContainsString('fill="#f34303"', $pdfHtml);
        $this->assertStringContainsString('fill="#00d27a"', $pdfHtml);
        $this->assertStringContainsString('fill="#ffb51b"', $pdfHtml);
    }

    public function test_project_export_rejects_flowcharts_not_linked_to_the_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Project Alpha']);
        $otherProject = Project::query()->create(['name' => 'Project Beta']);
        $chart = FlowChart::query()->create([
            'title' => 'Unrelated flow chart',
            'category' => 'process',
            'is_example' => false,
            'steps' => [],
        ]);
        $otherProject->flowCharts()->attach($chart);

        $this->from(route('projects.show', $project))
            ->post(route('projects.export', $project), [
                'sections' => ['flowcharts'],
                'flowchart_mode' => 'selected',
                'flow_chart_ids' => [$chart->id],
            ])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors('flow_chart_ids');
    }

    public function test_task_gantt_uses_planned_start_and_due_date_and_rejects_invalid_ranges(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Timeline project']);

        $this->post(route('projects.tasks.store', $project), [
            'title' => 'Build requirements',
            'status' => 'backlog',
            'priority' => 'high',
            'start_date' => '2026-11-01',
            'due_date' => '2026-11-05',
        ])->assertSessionHasNoErrors();

        $task = Task::query()->where('title', 'Build requirements')->firstOrFail();
        $this->assertSame('2026-11-01', $task->start_date->toDateString());
        $this->assertSame('2026-11-05', $task->due_date->toDateString());

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Task Gantt chart')
            ->assertSee('Nov 1–Nov 5, 2026');

        $this->get(route('projects.tasks.create', $project))
            ->assertOk()
            ->assertSee('Planned start');

        $this->put(route('tasks.update', $task), [
            'title' => $task->title,
            'status' => $task->status,
            'priority' => $task->priority,
            'start_date' => '2026-11-06',
            'due_date' => '2026-11-05',
        ])->assertSessionHasErrors('due_date');
        $this->assertSame('2026-11-01', $task->fresh()->start_date->toDateString());
    }
}
