<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Booking;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\User;
use App\Rules\AssignableUser;
use App\Services\AttachmentStorage;
use App\Services\WorkspaceNotifier;
use App\Support\AssignmentAccess;
use App\Support\DataTableQueryBuilder;
use App\Support\StatusBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class RepairTicketController extends Controller
{
    public function trackingForm(): View
    {
        return view('repairs.track');
    }

    public function track(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'ticket_number' => ['required', 'string', 'max:64'],
        ]);

        $ticket = RepairTicket::query()
            ->whereRaw('LOWER(ticket_number) = ?', [strtolower(trim($data['ticket_number']))])
            ->with(['updates' => fn ($updates) => $updates
                ->select(['id', 'repair_ticket_id', 'status', 'created_at'])
                ->oldest()])
            ->first();

        if (! $ticket) {
            return redirect()->route('repairs.track.form')
                ->withErrors(['ticket_number' => 'We could not find a repair with that service number.'])
                ->withInput();
        }

        return view('repairs.track', ['trackingTicket' => $ticket]);
    }

    public function index(Request $request): View
    {
        return view('tickets.index', [
            'statuses' => RepairTicket::STATUSES,
        ]);
    }

    public function datatable(Request $request)
    {
        $query = RepairTicket::query()->with(['customer', 'project', 'assignees']);
        if ($request->user()->hasRole('customer')) {
            $email = strtolower(trim($request->user()->email));
            $query->whereHas('customer', fn (Builder $customers) => $customers
                ->whereRaw('LOWER(email) = ?', [$email]));
        }

        if ($request->filled('status') && isset(RepairTicket::STATUSES[$request->input('status')])) {
            $query->where('status', $request->input('status'));
        }
        if ($request->input('assigned') === 'unassigned') {
            $query->whereDoesntHave('assignees');
        } elseif ($request->input('assigned') === 'mine') {
            $query->whereHas('assignees', fn (Builder $assignees) => $assignees->whereKey($request->user()->id));
        }

        return DataTables::eloquent(DataTableQueryBuilder::for($query, $request, [
            'status' => AllowedFilter::exact('status'),
            'priority' => AllowedFilter::exact('priority'),
            'ticket_number' => AllowedFilter::partial('ticket_number'),
            'device_model' => AllowedFilter::partial('device_model'),
        ]))
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

    public function create(Request $request): View|RedirectResponse
    {
        abort_if($request->user()->hasRole('customer'), 403);

        $booking = $request->filled('booking_id')
            ? Booking::query()->with('customer')->findOrFail($request->integer('booking_id'))
            : null;
        if ($booking?->repair_ticket_id) {
            return redirect()->route('tickets.show', $booking->repair_ticket_id);
        }
        $customer = $booking?->customer;

        return view('tickets.create', [
            'ticket' => new RepairTicket([
                'customer_id' => $customer?->id ?? ($request->integer('customer_id') ?: null),
                'device_type' => $booking ? 'Computer' : null,
                'device_model' => $booking?->device_model,
                'issue_description' => $booking
                    ? trim($booking->service_name."\n\n".($booking->request_notes ?? ''))
                    : null,
                'priority' => 'normal',
            ]),
            'booking' => $booking,
            'customers' => Customer::query()->orderBy('name')->get(),
            'users' => AssignmentAccess::users()->get(['id', 'name']),
            'statuses' => RepairTicket::STATUSES,
            'priorities' => RepairTicket::PRIORITIES,
        ]);
    }

    public function store(Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        AssignmentAccess::preventNonAdminAssignment($request, ['assigned_to', 'assignees', 'assignees_present']);

        $data = $request->validate([
            'customer_id' => ['required_without:booking_id', 'nullable', 'integer', 'exists:customers,id'],
            'booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
            'assigned_to' => ['nullable', 'integer', new AssignableUser],
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['integer', 'distinct', new AssignableUser],
            'assignees_present' => ['sometimes', 'boolean'],
            'device_type' => ['nullable', 'string', 'max:80'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'issue_description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_keys(RepairTicket::PRIORITIES))],
            'due_at' => ['nullable', 'date'],
        ]);
        $bookingId = $data['booking_id'] ?? null;
        unset($data['booking_id']);
        $assignees = $this->assigneeIds($request, $data);
        unset($data['assignees'], $data['assignees_present'], $data['assigned_to']);

        $ticket = DB::transaction(function () use ($data, $request, $assignees, $bookingId): RepairTicket {
            $booking = $bookingId
                ? Booking::query()->with('customer')->lockForUpdate()->findOrFail($bookingId)
                : null;
            if ($booking?->repair_ticket_id) {
                throw ValidationException::withMessages([
                    'booking_id' => 'This booking already has a repair ticket.',
                ]);
            }
            $customer = $booking?->customer;
            if ($booking && ! $customer) {
                $customer = Customer::query()->firstOrCreate(
                    ['email' => $booking->customer_email],
                    [
                        'name' => $booking->customer_name,
                        'phone' => $booking->customer_phone,
                    ],
                );
                $booking->update(['customer_id' => $customer->id]);
            }
            $customer ??= Customer::query()->findOrFail($data['customer_id']);
            $ticket = RepairTicket::create([
                ...$data,
                'customer_id' => $customer->id,
                'assigned_to' => $assignees[0] ?? null,
                'created_by_user_id' => $request->user()->id,
                'ticket_number' => 'TEMP-'.Str::upper(Str::random(12)),
                'project_id' => null,
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

            $ticket->assignees()->sync($assignees);
            $booking?->update(['repair_ticket_id' => $ticket->id]);

            return $ticket;
        });
        $ticket->load(['creator', 'assignees']);
        $notifier->notifyUsers(
            $ticket->assignees,
            $request->user(),
            'Repair ticket assigned',
            sprintf('You have been assigned repair ticket %s.', $ticket->ticket_number),
            route('tickets.show', $ticket),
        );
        $notifier->notifyUsers(
            collect([$ticket->creator]),
            $request->user(),
            'Repair ticket created',
            sprintf('Repair ticket %s was created.', $ticket->ticket_number),
            route('tickets.show', $ticket),
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Repair ticket created.');
    }

    public function show(Request $request, RepairTicket $ticket): View
    {
        if ($request->user()->hasRole('customer')) {
            abort_unless(
                strtolower(trim((string) $ticket->customer()->value('email'))) === strtolower(trim($request->user()->email)),
                404,
            );
        }

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
        if ($request->user()->hasRole('customer')) {
            if ($ticket->project_id) {
                $ticket->load(['billingDocuments' => fn ($documents) => $documents
                    ->whereIn('type', ['invoice', 'quotation'])
                    ->whereHas('repairTicket.project')]);
            } else {
                $ticket->setRelation('billingDocuments', collect());
            }
        }

        return view('tickets.show', [
            'ticket' => $ticket,
            'users' => AssignmentAccess::users()->get(['id', 'name']),
            'statuses' => RepairTicket::STATUSES,
            'priorities' => RepairTicket::PRIORITIES,
        ]);
    }

    public function update(Request $request, RepairTicket $ticket, WorkspaceNotifier $notifier): RedirectResponse
    {
        $ticket->loadMissing(['creator', 'assignees']);
        $existingAssigneeIds = $ticket->assignees->modelKeys();
        AssignmentAccess::preventNonAdminAssignment($request, ['assigned_to', 'assignees', 'assignees_present']);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(RepairTicket::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(RepairTicket::PRIORITIES))],
            'assigned_to' => ['nullable', 'integer', new AssignableUser],
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['integer', 'distinct', new AssignableUser],
            'assignees_present' => ['sometimes', 'boolean'],
            'diagnosis' => ['nullable', 'string', 'max:5000'],
            'due_at' => ['nullable', 'date'],
        ]);
        $assignees = $request->user()->hasRole('admin')
            ? $this->assigneeIds($request, $data)
            : $existingAssigneeIds;
        unset($data['assignees'], $data['assignees_present'], $data['assigned_to']);
        $statusChanged = $ticket->status !== $data['status'];
        DB::transaction(function () use ($ticket, $data, $request, $assignees, $statusChanged): void {
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
        $ticket->load(['creator', 'assignees']);
        $newAssigneeIds = array_diff($assignees, $existingAssigneeIds);
        $notifier->notifyUsers(
            $newAssigneeIds === [] ? [] : User::query()->whereKey($newAssigneeIds)->get(),
            $request->user(),
            'Repair ticket assigned',
            sprintf('You have been assigned repair ticket %s.', $ticket->ticket_number),
            route('tickets.show', $ticket),
        );
        $existingRecipients = collect([$ticket->creator])
            ->merge($existingAssigneeIds === [] ? collect() : User::query()->whereKey($existingAssigneeIds)->get());
        $notifier->notifyUsers(
            $existingRecipients,
            $request->user(),
            $statusChanged ? 'Repair ticket updated' : 'Repair ticket details updated',
            sprintf('Repair ticket %s was updated%s.', $ticket->ticket_number, $statusChanged ? ' to '.RepairTicket::STATUSES[$ticket->status] : ''),
            route('tickets.show', $ticket),
        );

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

    public function storeUpdate(Request $request, RepairTicket $ticket, AttachmentStorage $storage, WorkspaceNotifier $notifier): RedirectResponse
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
        $ticket->load(['creator', 'assignees']);
        $notifier->notifyUsers(
            collect([$ticket->creator])->merge($ticket->assignees),
            $request->user(),
            'Repair ticket update',
            sprintf('Repair ticket %s: %s.', $ticket->ticket_number, $update->title),
            route('tickets.show', $ticket),
        );

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket work step added.');
    }
}
