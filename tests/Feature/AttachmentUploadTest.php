<?php

namespace Tests\Feature;

use App\Models\BusinessExpense;
use App\Models\Customer;
use App\Models\CustomerMessageTemplate;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
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
            'priority' => 'medium',
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
        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('data-gallery="task-photos"', false)
            ->assertSee('name="caption"', false)
            ->assertSee('vendors/glightbox/glightbox.min.js');
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

        $this->patch(route('tasks.attachments.caption', [$task, $taskAttachment]), [
            'caption' => 'Updated inspection reference',
        ])->assertRedirect();

        $this->assertSame('Updated inspection reference', $taskAttachment->fresh()->caption);
    }

    public function test_task_photo_captions_can_only_be_changed_through_their_task(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Caption test project']);
        $task = $project->tasks()->create([
            'title' => 'First task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        $otherTask = $project->tasks()->create([
            'title' => 'Second task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        $this->post(route('tasks.attachments.store', $task), [
            'photos' => [UploadedFile::fake()->image('task-photo.png')],
            'caption' => 'Original caption',
        ])->assertRedirect();
        $attachment = $task->attachments()->firstOrFail();

        $this->patch(route('tasks.attachments.caption', [$otherTask, $attachment]), [
            'caption' => 'Tampered caption',
        ])->assertNotFound();

        $this->assertSame('Original caption', $attachment->fresh()->caption);
    }

    public function test_task_photo_caption_is_limited_to_255_characters(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Caption validation project']);
        $task = $project->tasks()->create([
            'title' => 'Caption validation task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        $this->post(route('tasks.attachments.store', $task), [
            'photos' => [UploadedFile::fake()->image('caption-validation.png')],
            'caption' => 'Original caption',
        ])->assertRedirect();
        $attachment = $task->attachments()->firstOrFail();

        $this->patch(route('tasks.attachments.caption', [$task, $attachment]), [
            'caption' => str_repeat('a', 256),
        ])->assertSessionHasErrors('caption');

        $this->assertSame('Original caption', $attachment->fresh()->caption);
    }

    public function test_project_photos_can_be_captioned_and_included_in_the_project_pdf(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Photo-ready project']);

        $this->post(route('projects.attachments.store', $project), [
            'photos' => [UploadedFile::fake()->image('architecture.png')],
            'caption' => 'System architecture',
        ])->assertRedirect();

        $attachment = $project->attachments()->firstOrFail();
        $this->patch(route('projects.attachments.caption', [$project, $attachment]), [
            'caption' => 'Updated architecture',
        ])->assertRedirect();
        $this->assertSame('Updated architecture', $attachment->fresh()->caption);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-gallery="project-photos"', false)
            ->assertSee('Updated architecture');

        $projectPdf = $this->post(route('projects.export', $project), [
            'sections' => ['photos'],
            'flowchart_mode' => 'all',
        ])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', substr($projectPdf->getContent(), 0, 4));
    }

    public function test_expense_photos_can_be_captioned_and_included_in_the_expense_pdf(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $expense = BusinessExpense::query()->create([
            'category' => 'services',
            'name' => 'Domain renewal',
            'amount' => 80,
            'frequency' => 'yearly',
            'due_date' => today()->addMonth(),
            'notes' => 'Renew the company domain.',
        ]);

        $this->post(route('expenses.attachments.store', ['services', $expense]), [
            'photos' => [UploadedFile::fake()->image('domain-receipt.png')],
            'caption' => 'Domain renewal receipt',
        ])->assertRedirect();

        $attachment = $expense->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertGreaterThan(0, Storage::disk('local')->size($attachment->path));
        $this->patch(route('expenses.attachments.caption', ['services', $expense, $attachment]), [
            'caption' => 'Updated renewal receipt',
        ])->assertRedirect();
        $this->assertSame('Updated renewal receipt', $attachment->fresh()->caption);

        $this->get(route('expenses.index', 'services'))
            ->assertOk()
            ->assertSee('data-gallery="expense-'.$expense->id.'"', false)
            ->assertSee('Export PDF');

        $expensePdf = $this->get(route('expenses.pdf', ['services', $expense]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', substr($expensePdf->getContent(), 0, 4));
    }

    public function test_rental_bills_and_services_accept_private_pdf_and_image_uploads(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());

        foreach (['rental', 'bills', 'services'] as $category) {
            $expense = BusinessExpense::query()->create([
                'category' => $category,
                'name' => ucfirst($category).' document test',
                'amount' => 80,
                'frequency' => 'yearly',
                'due_date' => today()->addMonth(),
            ]);

            $this->post(route('expenses.attachments.store', [$category, $expense]), [
                'documents' => [
                    UploadedFile::fake()->createWithContent('proof.pdf', "%PDF-1.4\nTest document\n%%EOF"),
                    UploadedFile::fake()->image('proof.png'),
                ],
                'caption' => ucfirst($category).' bill or renewal proof',
            ])->assertRedirect()->assertSessionHasNoErrors();

            $attachments = $expense->attachments()->get();
            $this->assertCount(2, $attachments);
            $pdf = $attachments->firstWhere('mime_type', 'application/pdf');
            $this->assertNotNull($pdf);
            Storage::disk('local')->assertExists($pdf->path);

            $this->get(route('attachments.show', $pdf))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf')
                ->assertHeader('x-content-type-options', 'nosniff');
        }

        $this->get(route('expenses.index', 'rental'))
            ->assertOk()
            ->assertSee('PDFs or pictures')
            ->assertSee('Open PDF');
    }

    public function test_expense_upload_rejects_non_pdf_and_non_image_files(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $expense = BusinessExpense::query()->create([
            'category' => 'bills',
            'name' => 'Unsupported upload test',
            'amount' => 80,
            'frequency' => 'monthly',
            'due_date' => today()->addMonth(),
        ]);

        $this->post(route('expenses.attachments.store', ['bills', $expense]), [
            'documents' => [UploadedFile::fake()->createWithContent('script.php', '<?php echo "no";')],
        ])->assertSessionHasErrors('documents.0');

        $this->assertSame(0, $expense->attachments()->count());
    }
}
