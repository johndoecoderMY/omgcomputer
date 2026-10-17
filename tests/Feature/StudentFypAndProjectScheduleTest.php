<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FlowChart;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\StudentFypSopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFypAndProjectScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_fyp_sop_examples_are_seeded_and_available_from_the_library(): void
    {
        $this->actingAs(User::factory()->create());
        $seeder = new StudentFypSopSeeder;
        $seeder->run();
        $seeder->run();

        $examples = FlowChart::query()->where('category', 'education')->get();
        $this->assertCount(6, $examples);
        $this->assertTrue($examples->every(fn (FlowChart $flowChart): bool => count($flowChart->steps) >= 6));
        $this->get(route('development.sops', ['category' => 'education']))
            ->assertOk()
            ->assertSee('Student FYP SOPs')
            ->assertSee('Student FYP');
        $this->get(route('development.flowcharts.create', ['category' => 'education']))
            ->assertOk()
            ->assertSee('Agree the FYP scope, outcomes, and communication plan');
    }

    public function test_project_events_can_be_scheduled_and_are_identifiable_in_the_calendar(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'FYP Research Project']);
        $otherProject = Project::query()->create(['name' => 'Mobile App FYP']);
        $startsAt = now()->addDays(4)->setTime(10, 30);
        $endsAt = $startsAt->copy()->addHour();

        $this->post(route('projects.events.store', $project), [
            'title' => 'Weekly consultation',
            'event_type' => 'consultation',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'ends_at' => $endsAt->format('Y-m-d\TH:i'),
            'location' => 'Lab 2',
            'description' => 'Review research questions and next steps.',
        ])->assertRedirect(route('projects.show', $project));

        $event = Event::query()->firstOrFail();
        $this->assertSame($project->id, $event->project_id);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Project schedule')
            ->assertSee('project-mini-calendar')
            ->assertSee('Upcoming events')
            ->assertSee('Weekly consultation')
            ->assertSee('Lab 2');
        $this->get(route('projects.calendar', ['month' => $startsAt->format('Y-m')]))
            ->assertOk()
            ->assertSee('project-calendar')
            ->assertSee('timeGridWeek');
        $this->getJson(route('projects.calendar.events', [
            'start' => $startsAt->copy()->startOfMonth()->toIso8601String(),
            'end' => $startsAt->copy()->endOfMonth()->addDay()->startOfDay()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Weekly consultation')
            ->assertJsonPath('0.extendedProps.project', 'FYP Research Project')
            ->assertJsonPath('0.extendedProps.type', 'Consultation');
        $this->getJson(route('projects.calendar.events', [
            'project_id' => $project->id,
            'start' => $startsAt->copy()->startOfDay()->toIso8601String(),
            'end' => $startsAt->copy()->addDay()->startOfDay()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Weekly consultation');
        $this->delete(route('projects.events.destroy', [$otherProject, $event]))->assertNotFound();
        $this->delete(route('projects.events.destroy', [$project, $event]))
            ->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    public function test_project_event_validation_rejects_an_end_before_the_start(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'FYP Prototype']);
        $startsAt = now()->addDay()->startOfHour();

        $this->from(route('projects.show', $project))
            ->post(route('projects.events.store', $project), [
                'title' => 'Progress review',
                'event_type' => 'progress_review',
                'starts_at' => $startsAt->format('Y-m-d\TH:i'),
                'ends_at' => $startsAt->copy()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors('ends_at');
        $this->assertDatabaseCount('events', 0);
    }

    public function test_calendar_can_create_and_display_events_without_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Optional event project']);
        $startsAt = now()->addDays(2)->setTime(13, 0);

        $this->get(route('projects.calendar'))
            ->assertOk()
            ->assertSee('Add event')
            ->assertSee('General event (not linked to a project)');
        $this->post(route('events.store'), [
            'title' => 'Department planning',
            'event_type' => 'other',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'return_to' => 'calendar',
        ])->assertRedirect(route('projects.calendar'));

        $event = Event::query()->firstOrFail();
        $this->assertNull($event->project_id);
        $this->getJson(route('projects.calendar.events', [
            'start' => $startsAt->copy()->startOfDay()->toIso8601String(),
            'end' => $startsAt->copy()->addDay()->startOfDay()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonPath('0.title', 'Department planning')
            ->assertJsonPath('0.extendedProps.project', 'General event')
            ->assertJsonPath('0.url', null);
        $this->delete(route('events.destroy', $event))
            ->assertRedirect(route('projects.calendar'));
        $this->assertDatabaseMissing('events', ['id' => $event->id]);

        $this->post(route('events.store'), [
            'project_id' => $project->id,
            'title' => 'Project-linked review',
            'event_type' => 'progress_review',
            'starts_at' => $startsAt->copy()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('projects.calendar'));
        $linkedEvent = Event::query()->where('title', 'Project-linked review')->firstOrFail();
        $project->delete();
        $this->assertDatabaseHas('events', [
            'id' => $linkedEvent->id,
            'project_id' => null,
        ]);
    }
}
