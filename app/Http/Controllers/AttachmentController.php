<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\BusinessExpense;
use App\Models\BusinessExpense;
use App\Models\CustomerMessageTemplate;
use App\Models\Project;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\Task;
use App\Services\AttachmentStorage;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends Controller
{
    public function storeProject(Request $request, Project $project, AttachmentStorage $storage): RedirectResponse
    {
        $data = $request->validate(AttachmentStorage::validationRules());
        $storage->store($project->attachments(), $data['photos'], $request->user(), $data['caption'] ?? null);

        return back()->with('success', 'Project photos uploaded.');
    }

    public function updateProjectCaption(Request $request, Project $project, Attachment $attachment): RedirectResponse
    {
        $this->updateCaption($request, $project->attachments(), $attachment);

        return back()->with('success', 'Project photo caption updated.');
    }

    public function storeExpense(Request $request, string $category, BusinessExpense $expense, AttachmentStorage $storage): RedirectResponse
    {
        abort_unless($expense->category === $category, 404);
        $data = $request->validate(AttachmentStorage::validationRules());
        $storage->store($expense->attachments(), $data['photos'], $request->user(), $data['caption'] ?? null);

        return back()->with('success', 'Expense photos uploaded.');
    }

    public function updateExpenseCaption(Request $request, string $category, BusinessExpense $expense, Attachment $attachment): RedirectResponse
    {
        abort_unless($expense->category === $category, 404);
        $this->updateCaption($request, $expense->attachments(), $attachment);

        return back()->with('success', 'Expense photo caption updated.');
    }

    public function storeTask(Request $request, Task $task, AttachmentStorage $storage): RedirectResponse
    {
        $data = $request->validate(AttachmentStorage::validationRules());
        $storage->store($task->attachments(), $data['photos'], $request->user(), $data['caption'] ?? null);

        return back()->with('success', 'Task photos uploaded.');
    }

    public function updateTaskAttachmentCaption(Request $request, Task $task, Attachment $attachment): RedirectResponse
    {
        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $task->attachments()
            ->whereKey($attachment->getKey())
            ->firstOrFail()
            ->update(['caption' => $data['caption'] ?? null]);

        return back()->with('success', 'Task photo caption updated.');
    }

    public function storeTicket(Request $request, RepairTicket $ticket, AttachmentStorage $storage): RedirectResponse
    {
        $data = $request->validate(AttachmentStorage::validationRules());
        $storage->store($ticket->attachments(), $data['photos'], $request->user(), $data['caption'] ?? null);

        return back()->with('success', 'Ticket photos uploaded.');
    }

    public function storeTicketUpdate(Request $request, RepairTicket $ticket, RepairTicketUpdate $update, AttachmentStorage $storage): RedirectResponse
    {
        abort_unless($update->repair_ticket_id === $ticket->id, 404);
        $data = $request->validate(AttachmentStorage::validationRules());
        $storage->store($update->attachments(), $data['photos'], $request->user(), $data['caption'] ?? null);

        return back()->with('success', 'Step photos uploaded.');
    }

    public function storeMessageTemplate(Request $request, CustomerMessageTemplate $customerMessage, AttachmentStorage $storage): RedirectResponse
    {
        $data = $request->validate(AttachmentStorage::validationRules());
        $storage->store($customerMessage->attachments(), $data['photos'], $request->user(), $data['caption'] ?? null);

        return back()->with('success', 'Copywriting reference photos uploaded.');
    }

    public function show(Request $request, Attachment $attachment): BinaryFileResponse
    {
        abort_if(
            $request->user()->hasRole('customer')
                && in_array($attachment->attachable_type, [BusinessExpense::class, Project::class, Task::class], true),
            404,
        );
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return response()->file(Storage::disk('local')->path($attachment->path), [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function updateCaption(Request $request, MorphMany $attachments, Attachment $attachment): void
    {
        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $attachments->whereKey($attachment->getKey())->firstOrFail()
            ->update(['caption' => $data['caption'] ?? null]);
    }
}
