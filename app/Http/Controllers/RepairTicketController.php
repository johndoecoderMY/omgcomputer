<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\User;
use App\Services\AttachmentStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RepairTicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = RepairTicket::query()->with(['customer', 'project', 'assignee']);

        if ($request->filled('status') && isset(RepairTicket::STATUSES[$request->input('status')])) {
            $query->where('status', $request->input('status'));
        }
        if ($request->input('assigned') === 'unassigned') {
            $query->whereNull('assigned_to');
        } elseif ($request->input('assigned') === 'mine') {
            $query->where('assigned_to', $request->user()->id);
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn ($tickets) => $tickets
                ->where('ticket_number', 'like', "%{$search}%")
                ->orWhere('device_model', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customers) => $customers->where('name', 'like', "%{$search}%")));
        }

        return view('tickets.index', [
            'tickets' => $query->latest('received_at')->paginate(25)->withQueryString(),
            'statuses' => RepairTicket::STATUSES,
        ]);
    }

    public function create(Request $request): View
    {
        return view('tickets.create', [
            'ticket' => new RepairTicket([
                'customer_id' => $request->integer('customer_id') ?: null,
                'project_id' => $request->integer('project_id') ?: null,
                'priority' => 'normal',
            ]),
            'customers' => Customer::query()->orderBy('name')->get(),
            'projects' => Project::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => RepairTicket::STATUSES,
            'priorities' => RepairTicket::PRIORITIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'device_type' => ['nullable', 'string', 'max:80'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'issue_description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_keys(RepairTicket::PRIORITIES))],
            'due_at' => ['nullable', 'date'],
        ]);

        $ticket = DB::transaction(function () use ($data, $request): RepairTicket {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $ticket = RepairTicket::create([
                ...$data,
                'ticket_number' => 'TEMP-' . Str::upper(Str::random(12)),
                'project_id' => $data['project_id'] ?? $customer->project_id,
                'status' => 'new',
                'received_at' => now(),
            ]);
            $ticket->update([
                'ticket_number' => 'RT-' . now()->format('Y') . '-' . str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
            ]);
            $ticket->updates()->create([
                'user_id' => $request->user()->id,
                'title' => 'Device received',
                'status' => 'new',
                'notes' => $data['issue_description'],
            ]);
            $ticket->updates()->create([
                'user_id' => $request->user()->id,
                'title' => 'Device received',
                'status' => 'new',
                'notes' => $data['issue_description'],
            ]);

            return $ticket;
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Repair ticket created.');
    }

    public function show(RepairTicket $ticket): View
    {
        $ticket->load([
            'customer.project',
            'project',
            'assignee',
            'billingDocuments',
            'attachments.uploader',
            'updates.user',
            'updates.attachments.uploader',
            'attachments.uploader',
            'updates.user',
            'updates.attachments.uploader',
        ]);

        return view('tickets.show', [
            'ticket' => $ticket,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => RepairTicket::STATUSES,
            'priorities' => RepairTicket::PRIORITIES,
        ]);
    }

    public function update(Request $request, RepairTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(RepairTicket::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(RepairTicket::PRIORITIES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
        ]);
        DB::transaction(function () use ($ticket, $data, $request): void {
            $statusChanged = $ticket->status !== $data['status'];
            DB::transaction(function () use ($ticket, $data, $request): void {
                $statusChanged = $ticket->status !== $data['status'];
                $ticket->update($data);
                if ($statusChanged) {
                    $ticket->updates()->create([
                        'user_id' => $request->user()->id,
                        'title' => 'Status changed to ' . RepairTicket::STATUSES[$data['status']],
                        'status' => $data['status'],
                        'notes' => $data['diagnosis'] ?? null,
                    ]);
                }
            });
            if ($statusChanged) {
                $ticket->updates()->create([
                    'user_id' => $request->user()->id,
                    'title' => 'Status changed to ' . RepairTicket::STATUSES[$data['status']],
                    'status' => $data['status'],
                    'notes' => $data['diagnosis'] ?? null,
                ]);
            }
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Repair ticket updated.');
    }

    public function storeUpdate(Request $request, RepairTicket $ticket, AttachmentStorage $storage): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'status' => ['required', Rule::in(array_keys(RepairTicket::STATUSES))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photos' => ['nullable', 'array', 'max:8'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);
        $photos = $data['photos'] ?? [];
        unset($data['photos'], $data['caption']);

        $update = DB::transaction(function () use ($ticket, $data, $request): RepairTicketUpdate {
            $ticket->update(['status' => $data['status']]);

            return $ticket->updates()->create([
                ...$data,
                'user_id' => $request->user()->id,
            ]);
        });

        if ($photos) {
            $storage->store($update->attachments(), $photos, $request->user(), $request->input('caption'));
        }

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket work step added.');
    }
}