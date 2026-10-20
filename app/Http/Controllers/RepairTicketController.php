<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\User;
use App\Services\AttachmentStorage;
use App\Support\StatusBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class RepairTicketController extends Controller
{
    public function index(): View
    {
        return view('tickets.index', [
            'statuses' => RepairTicket::STATUSES,
        ]);
    }

    public function datatable(Request $request)
    {
        $query = RepairTicket::query()->with(['customer', 'project', 'assignees']);

        if ($request->filled('status') && isset(RepairTicket::STATUSES[$request->input('status')])) {
            $query->where('status', $request->input('status'));
        }
        if ($request->input('assigned') === 'unassigned') {
            $query->whereDoesntHave('assignees');
        } elseif ($request->input('assigned') === 'mine') {
            $query->whereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($request->user()->id));
        }

        return DataTables::eloquent($query)
            ->filter(function (Builder $tickets) use ($request): void {
                $search = trim((string) $request->input('search.value', ''));
                if ($search === '') {
                    return;
                }

                $tickets->where(function (Builder $matches) use ($search): void {
                    $matches->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('device_type', 'like', "%{$search}%")
                        ->orWhere('device_model', 'like', "%{$search}%")
                        ->orWhere('serial_number', 'like', "%{$search}%")
                        ->orWhere('issue_description', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn (Builder $project) => $project->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->where('name', 'like', "%{$search}%"));
                });
            }, true)
            ->addColumn('ticket', fn (RepairTicket $ticket): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a><div class="small text-body-secondary">%s</div>',
                e(route('tickets.show', $ticket)),
                e($ticket->ticket_number),
                e($ticket->received_at->format('M j, Y')),
            ))
            ->addColumn('customer_name', fn (RepairTicket $ticket): string => $ticket->customer->name)
            ->addColumn('device', fn (RepairTicket $ticket): string => $ticket->device_model ?: $ticket->device_type ?: '—')
            ->addColumn('project_name', fn (RepairTicket $ticket): string => $ticket->project?->name ?: '—')
            ->addColumn('status_label', fn (RepairTicket $ticket): string => (string) StatusBadge::make(
                $ticket->status,
                RepairTicket::STATUSES[$ticket->status] ?? $ticket->status,
            ))
            ->addColumn('priority_label', fn (RepairTicket $ticket): string => RepairTicket::PRIORITIES[$ticket->priority] ?? $ticket->priority)
            ->addColumn('assignee_name', fn (RepairTicket $ticket): string => e($ticket->assignees->pluck('name')->join(', ') ?: 'Unassigned'))
            ->addColumn('due_date', fn (RepairTicket $ticket): string => $ticket->due_at?->format('M j, Y') ?? '—')
            ->addColumn('actions', fn (RepairTicket $ticket): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">Open</a>',
                e(route('tickets.show', $ticket)),
            ))
            ->rawColumns(['ticket', 'status_label', 'actions'])
            ->toJson();
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
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['integer', 'distinct', 'exists:users,id'],
            'assignees_present' => ['sometimes', 'boolean'],
            'device_type' => ['nullable', 'string', 'max:80'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'issue_description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_keys(RepairTicket::PRIORITIES))],
            'due_at' => ['nullable', 'date'],
        ]);
        $assignees = $this->assigneeIds($request, $data);
        unset($data['assignees'], $data['assignees_present'], $data['assigned_to']);

        $ticket = DB::transaction(function () use ($data, $request, $assignees): RepairTicket {
            $customer = Customer::query()->findOrFail($data['customer_id']);
            $ticket = RepairTicket::create([
                ...$data,
                'assigned_to' => $assignees[0] ?? null,
                'ticket_number' => 'TEMP-'.Str::upper(Str::random(12)),
                'project_id' => $data['project_id'] ?? $customer->project_id,
                'status' => 'new',
                'received_at' => now(),
            ]);
            $ticket->update([
                'ticket_number' => 'RT-'.now()->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
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

            $ticket->assignees()->sync($assignees);

            return $ticket;
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Repair ticket created.');
    }

    public function show(RepairTicket $ticket): View
    {
        $ticket->load([
            'customer.project',
            'project',
            'assignees',
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
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['integer', 'distinct', 'exists:users,id'],
            'assignees_present' => ['sometimes', 'boolean'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
        ]);
        $assignees = $this->assigneeIds($request, $data);
        unset($data['assignees'], $data['assignees_present'], $data['assigned_to']);
        DB::transaction(function () use ($ticket, $data, $request, $assignees): void {
            $statusChanged = $ticket->status !== $data['status'];
            DB::transaction(function () use ($ticket, $data, $request, $assignees): void {
                $statusChanged = $ticket->status !== $data['status'];
                $ticket->update($data);
                $ticket->assignees()->sync($assignees);
                $ticket->update(['assigned_to' => $assignees[0] ?? null]);
                if ($statusChanged) {
                    $ticket->updates()->create([
                        'user_id' => $request->user()->id,
                        'title' => 'Status changed to '.RepairTicket::STATUSES[$data['status']],
                        'status' => $data['status'],
                        'notes' => $data['diagnosis'] ?? null,
                    ]);
                }
            });
            if ($statusChanged) {
                $ticket->updates()->create([
                    'user_id' => $request->user()->id,
                    'title' => 'Status changed to '.RepairTicket::STATUSES[$data['status']],
                    'status' => $data['status'],
                    'notes' => $data['diagnosis'] ?? null,
                ]);
            }
        });

        return redirect()->route('tickets.show', $ticket)->with('success', 'Repair ticket updated.');
    }

    private function assigneeIds(Request $request, array $data): array
    {
        $ids = $request->exists('assignees_present')
            ? ($data['assignees'] ?? [])
            : (array_key_exists('assignees', $data)
                ? $data['assignees']
                : (isset($data['assigned_to']) ? [(int) $data['assigned_to']] : []));

        return array_values(array_unique(array_map('intval', $ids)));
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
