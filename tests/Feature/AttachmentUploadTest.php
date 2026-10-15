<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerMessageTemplate;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_photos_can_be_attached_to_tasks_ticket_steps_tickets_and_templates(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::query()->create(['name' => 'Repair SOP project']);
        $task = $project->tasks()->create([
            'title' => 'Document inspection steps',
            'status' => 'in_progress',
            'priority' => 'normal',
        ]);
        $customer = Customer::query()->create(['name' => 'Photo Test Customer', 'email' => 'photo@example.test']);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-PHOTO-001',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'issue_description' => 'Device does not start.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        $template = CustomerMessageTemplate::query()->create([
            'slug' => 'photo-upload-template',
            'name' => 'Photo upload test',
            'stage' => 'general',
            'channel' => 'email',
            'body' => 'Please see the attached reference.',
        ]);

        $largePhoto = UploadedFile::fake()->image('task-step.png', 2400, 1600);
        $originalSize = $largePhoto->getSize();
        $this->post(route('tasks.attachments.store', $task), [
            'photos' => [$largePhoto],
            'caption' => 'Inspection checklist reference',
        ])->assertRedirect();

        $this->post(route('tickets.attachments.store', $ticket), [
            'photos' => [UploadedFile::fake()->image('device-intake.png')],
            'caption' => 'Device condition at intake',
        ])->assertRedirect();

        $this->post(route('tickets.steps.store', $ticket), [
            'title' => 'Inspect power input',
            'status' => 'diagnosing',
            'notes' => 'Check adapter output and charging port before opening the chassis.',
            'photos' => [UploadedFile::fake()->image('power-input.png')],
            'caption' => 'Power input inspection',
        ])->assertRedirect();

        $this->post(route('customer-messages.attachments.store', $template), [
            'photos' => [UploadedFile::fake()->image('message-reference.png')],
            'caption' => 'Reference image for customer update',
        ])->assertRedirect();

        $taskAttachment = $task->attachments()->firstOrFail();
        $step = RepairTicketUpdate::query()->where('repair_ticket_id', $ticket->id)->where('title', 'Inspect power input')->firstOrFail();
        $this->assertSame(1, $ticket->attachments()->count());
        $this->assertSame(1, $step->attachments()->count());
        $this->assertSame(1, $template->attachments()->count());
        Storage::disk('local')->assertExists($taskAttachment->path);
        $this->assertSame('image/webp', $taskAttachment->mime_type);
        $this->assertSame('webp', pathinfo($taskAttachment->path, PATHINFO_EXTENSION));
        $this->assertLessThan($originalSize, $taskAttachment->size);
        [$storedWidth, $storedHeight] = getimagesize(Storage::disk('local')->path($taskAttachment->path));
        $this->assertLessThanOrEqual(1920, max($storedWidth, $storedHeight));
        $this->get(route('attachments.show', $taskAttachment))
            ->assertOk()
            ->assertHeader('content-type', 'image/webp');
    }
}