<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Event;
use App\Models\FlowChart;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\StudentFypSopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentFypAndProjectScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_system_calendar_and_a_project_scoped_calendar(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $project = Project::query()->create(['name' => 'Laptop Repair Project']);
        $otherProject = Project::query()->create(['name' => 'Network Upgrade']);
        $start = now()->addDays(3);
        Event::query()->create([
            'project_id' => $project->id,
            'title' => 'Laptop project review',
            'event_type' => 'progress_review',
            'starts_at' => $start,
        ]);
        Event::query()->create([
            'project_id' => $otherProject->id,
            'title' => 'Network project review',
            'event_type' => 'progress_review',
            'starts_at' => $start->copy()->addHour(),
        ]);
        $calendarBooking = Booking::query()->create([
            'reference' => 'BK-CALENDAR-TEST',
            'customer_name' => 'Calendar customer',
            'customer_email' => 'calendar@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'laptop-cleaning',
            'service_name' => 'Laptop cleaning',
            'starts_at' => $start->copy()->addHours(2),
            'duration_minutes' => 60,
            'status' => 'confirmed',
        ]);
        $task = Task::query()->create([
            'project_id' => $project->id,
            'created_by_user_id' => $admin->id,
            'title' => 'Calendar due-date task',
            'status' => 'in_progress',
            'priority' => 'high',
            'start_date' => $start->copy()->addDay()->toDateString(),
            'due_date' => $start->copy()->addDay()->toDateString(),
        ]);

        $this->get(route('calendar'))
            ->assertOk()
            ->assertSee('My Calendar')
            ->assertSee('System');
        $this->assertSame('/calendar', parse_url(route('calendar'), PHP_URL_PATH));
        $this->get(route('projects.calendar', $project))
            ->assertOk()
            ->assertSee('Laptop Repair Project calendar')
            ->assertSee('name="project_id" value="'.$project->id.'"', false);
        $this->assertSame('/projects/'.$project->id.'/calendar', parse_url(route('projects.calendar', $project), PHP_URL_PATH));

        $this->getJson(route('projects.calendar.events', [
            'project' => $project,
            'start' => $start->copy()->subDay()->toIso8601String(),
            'end' => $start->copy()->addDay()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Laptop project review');

        $this->getJson(route('calendar.events', [
            'start' => $start->copy()->subDay()->toIso8601String(),
            'end' => $start->copy()->addDays(2)->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(4)
            ->assertJsonFragment([
                'kind' => 'booking',
                'reference' => 'BK-CALENDAR-TEST',
                'booking_url' => route('bookings.show', $calendarBooking),
            ])
            ->assertJsonFragment([
                'kind' => 'task',
                'task_title' => 'Calendar due-date task',
                'task_url' => route('tasks.show', $task),
                'status' => Task::STATUSES['in_progress'],
            ])
            ->assertJsonFragment([
                'backgroundColor' => Event::BACKGROUND_COLORS['progress_review'],
                'textColor' => Event::COLORS['progress_review'],
            ]);
    }

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
            ->assertSee('SOP Library')
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
            ->assertSee(route('projects.calendar', $project), false)
            ->assertSee('Upcoming events')
            ->assertSee('Weekly consultation')
            ->assertSee('Lab 2');
        $this->get(route('calendar', ['month' => $startsAt->format('Y-m')]))
            ->assertOk()
            ->assertSee('project-calendar')
            ->assertSee('timeGridWeek');
        $this->get(route('calendar'))
            ->assertOk()
            ->assertSee('My Calendar')
            ->assertSee('event-details-modal')
            ->assertSee("moreLinkClick: 'popover'", false)
            ->assertSee('text-overflow: ellipsis', false)
            ->assertSee("setProperty('background-color', backgroundColor, 'important')", false)
            ->assertSee("setProperty('opacity', '1', 'important')", false)
            ->assertSee("setProperty('color', textColor, 'important')", false)
            ->assertSee('eventClick:', false);
        $this->get(route('projects.calendar', $project))
            ->assertOk()
            ->assertSee($project->name.' calendar')
            ->assertSee(route('projects.calendar.events', $project), false)
            ->assertSee('data-event-reference-label', false)
            ->assertSee('name="project_id" value="'.$project->id.'"', false);
        $this->getJson(route('calendar.events', [
            'start' => $startsAt->copy()->startOfMonth()->toIso8601String(),
            'end' => $startsAt->copy()->endOfMonth()->addDay()->startOfDay()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.title', 'Weekly consultation')
            ->assertJsonPath('0.extendedProps.project', 'FYP Research Project')
            ->assertJsonPath('0.backgroundColor', Event::BACKGROUND_COLORS['consultation'])
            ->assertJsonPath('0.borderColor', Event::BACKGROUND_COLORS['consultation'])
            ->assertJsonPath('0.textColor', Event::COLORS['consultation'])
            ->assertJsonPath('0.extendedProps.project_url', route('projects.show', $project).'#project-schedule')
            ->assertJsonPath('0.extendedProps.type', 'Consultation');
        $this->getJson(route('projects.calendar.events', [
            'project' => $project,
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

        $this->get(route('calendar'))
            ->assertOk()
            ->assertSee('Add event')
            ->assertSee('General event (not linked to a project)');
        $this->post(route('events.store'), [
            'title' => 'Department planning',
            'event_type' => 'other',
            'starts_at' => $startsAt->format('Y-m-d\TH:i'),
            'return_to' => 'calendar',
        ])->assertRedirect(route('calendar'));

        $event = Event::query()->firstOrFail();
        $this->assertNull($event->project_id);
        $this->getJson(route('calendar.events', [
            'start' => $startsAt->copy()->startOfDay()->toIso8601String(),
            'end' => $startsAt->copy()->addDay()->startOfDay()->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonPath('0.title', 'Department planning')
            ->assertJsonPath('0.extendedProps.project', 'General event')
            ->assertJsonPath('0.url', null);
        $this->delete(route('events.destroy', $event))
            ->assertRedirect(route('calendar'));
        $this->assertDatabaseMissing('events', ['id' => $event->id]);

        $this->post(route('events.store'), [
            'project_id' => $project->id,
            'title' => 'Project-linked review',
            'event_type' => 'progress_review',
            'starts_at' => $startsAt->copy()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('calendar'));
        $linkedEvent = Event::query()->where('title', 'Project-linked review')->firstOrFail();
        $project->delete();
        $this->assertDatabaseHas('events', [
            'id' => $linkedEvent->id,
            'project_id' => null,
        ]);
    }
}
