<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\CustomerMessageTemplate;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\Task;
use App\Services\AttachmentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends Controller
{
    public function storeTask(Request $request, Task $task, AttachmentStorage $storage): RedirectResponse
    {
        $data = $request->validate(AttachmentStorage::validationRules());
        $storage->store($task->attachments(), $data['photos'], $request->user(), $data['caption'] ?? null);

        return back()->with('success', 'Task photos uploaded.');
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

    public function show(Attachment $attachment): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return response()->file(Storage::disk('local')->path($attachment->path), [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}