<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_assignment_notifies_the_assignee(): void
    {
        Notification::fake();
        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::query()->create(['name' => 'Mentoring portal']);

        $this->actingAs($actor)
            ->post(route('projects.tasks.store', $project), [
                'title' => 'Review the student proposal',
                'status' => 'backlog',
                'priority' => 'medium',
                'assigned_to' => $assignee->id,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Task assigned to you'
            && str_contains($notification->message, 'Review the student proposal'));
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_assigned_subtasks_notify_their_assignee(): void
    {
        Notification::fake();
        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::query()->create(['name' => 'Student prototype']);
        $parent = $project->tasks()->create([
            'title' => 'Build the prototype',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->actingAs($actor)
            ->post(route('tasks.subtasks.store', $parent), [
                'title' => 'Test the login flow',
                'status' => 'backlog',
                'priority' => 'medium',
                'assigned_to' => $assignee->id,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => str_contains($notification->message, 'Test the login flow'));
    }

    public function test_project_events_notify_project_task_assignees(): void
    {
        Notification::fake();
        $actor = User::factory()->create();
        $assignee = User::factory()->create();
        $project = Project::query()->create(['name' => 'Student FYP']);
        $project->tasks()->create([
            'assigned_to' => $assignee->id,
            'title' => 'Prepare a progress review',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->actingAs($actor)
            ->post(route('events.store'), [
                'project_id' => $project->id,
                'title' => 'FYP progress review',
                'event_type' => 'progress_review',
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Project event scheduled'
            && str_contains($notification->message, 'FYP progress review'));
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_users_can_read_and_clear_their_notifications(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Notification test project']);
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => WorkspaceNotification::class,
            'data' => [
                'title' => 'Task assigned to you',
                'message' => 'A task needs your attention.',
                'url' => route('projects.show', $project),
            ],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('Task assigned to you');

        $this->get(route('notifications.read', $notification))
            ->assertRedirect(route('projects.show', $project));
        $this->assertNotNull($notification->fresh()->read_at);

        $unread = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => WorkspaceNotification::class,
            'data' => ['title' => 'Another update', 'message' => 'Details.'],
        ]);
        $this->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($unread->fresh()->read_at);
    }

    public function test_users_cannot_read_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => WorkspaceNotification::class,
            'data' => ['title' => 'Private update', 'message' => 'Details.'],
        ]);

        $this->actingAs($otherUser)
            ->get(route('notifications.read', $notification))
            ->assertNotFound();
    }
}
