<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RepairTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $status = is_string($status) && array_key_exists($status, RepairTicket::STATUSES) ? $status : null;
        $priority = $request->query('priority');
        $priority = is_string($priority) && array_key_exists($priority, RepairTicket::PRIORITIES) ? $priority : null;
        $search = trim((string) $request->query('search', ''));
        $assignedFilter = $request->query('assigned');

        $query = RepairTicket::query()
            ->with(['assignee:id,name', 'creator:id,name'])
            ->when($status !== null, fn (Builder $builder): Builder => $builder->where('status', $status))
            ->when($priority !== null, fn (Builder $builder): Builder => $builder->where('priority', $priority))
            ->when($assignedFilter === 'unassigned', fn (Builder $builder): Builder => $builder->whereNull('assigned_to'))
            ->when($assignedFilter === 'mine', fn (Builder $builder): Builder => $builder->where('assigned_to', $request->user()->id))
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('device_model', 'like', "%{$search}%");
                });
            });

        return view('tickets.index', [
            'tickets' => $query->latest()->get(),
            'statuses' => RepairTicket::STATUSES,
            'priorities' => RepairTicket::PRIORITIES,
            'statusFilter' => $status,
            'priorityFilter' => $priority,
            'assignedFilter' => $assignedFilter,
            'search' => $search,
            'teamMembers' => User::query()->orderBy('name')->get(['id', 'name']),
            'counts' => [
                'open' => RepairTicket::query()->where('status', '!=', 'closed')->count(),
                'waiting_parts' => RepairTicket::query()->where('status', 'waiting_parts')->count(),
                'ready' => RepairTicket::query()->where('status', 'ready_for_pickup')->count(),
                'unassigned' => RepairTicket::query()->whereNull('assigned_to')->where('status', '!=', 'closed')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $customerId = filter_var($request->query('customer'), FILTER_VALIDATE_INT);
        $customer = $customerId === false ? null : Customer::query()->find($customerId);

        return view('tickets.create', [
            'priorities' => RepairTicket::PRIORITIES,
            'teamMembers' => User::query()->orderBy('name')->get(['id', 'name']),
            'customer' => $customer,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:180'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'device_type' => ['required', 'string', 'max:80'],
            'device_model' => ['nullable', 'string', 'max:180'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'subject' => ['required', 'string', 'max:180'],
            'issue_description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_keys(RepairTicket::PRIORITIES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        unset($validated['images']);

        $ticket = DB::transaction(function () use ($request, $validated): RepairTicket {
            $customer = $this->findOrCreateCustomer($validated);
            $ticket = RepairTicket::query()->create([
                ...$validated,
                'customer_id' => $customer->id,
                'created_by' => $request->user()->id,
                'ticket_number' => 'TMP-'.Str::uuid(),
                'status' => 'received',
            ]);
            $ticket->update([
                'ticket_number' => sprintf('OMG-%s-%06d', now()->format('Y'), $ticket->id),
            ]);

            return $ticket;
        });
        $this->appendImages($request, $ticket);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('status', 'Repair ticket created.');
    }

    public function show(RepairTicket $repairTicket): View
    {
        $repairTicket->load(['creator:id,name', 'assignee:id,name', 'customer:id,name,email,phone']);

        return view('tickets.show', [
            'ticket' => $repairTicket,
            'statuses' => RepairTicket::STATUSES,
            'priorities' => RepairTicket::PRIORITIES,
            'teamMembers' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, RepairTicket $repairTicket): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(RepairTicket::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(RepairTicket::PRIORITIES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'diagnostic_notes' => ['nullable', 'string', 'max:5000'],
            'quoted_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'due_date' => ['nullable', 'date'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        unset($validated['images']);
        $repairTicket->update($validated);
        $this->appendImages($request, $repairTicket);

        return redirect()
            ->route('tickets.show', $repairTicket)
            ->with('status', 'Ticket updated.');
    }

    public function image(RepairTicket $repairTicket, int $image)
    {
        $path = $repairTicket->images[$image] ?? null;
        abort_unless(is_string($path), 404);

        return Storage::disk('local')->response($path);
    }

    private function findOrCreateCustomer(array $ticket): Customer
    {
        $email = filled($ticket['customer_email'] ?? null) ? mb_strtolower(trim($ticket['customer_email'])) : null;
        $phone = filled($ticket['customer_phone'] ?? null) ? trim($ticket['customer_phone']) : null;
        $customer = $email ? Customer::query()->whereRaw('LOWER(email) = ?', [$email])->first() : null;
        $customer ??= $phone ? Customer::query()->where('phone', $phone)->first() : null;

        if ($customer) {
            $customer->update([
                'name' => $ticket['customer_name'],
                'email' => $email ?? $customer->email,
                'phone' => $phone ?? $customer->phone,
            ]);

            return $customer;
        }

        return Customer::query()->create([
            'name' => $ticket['customer_name'],
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    private function appendImages(Request $request, RepairTicket $ticket): void
    {
        $paths = $ticket->images ?? [];
        foreach ($request->file('images', []) as $image) {
            $path = $image->store('repair-tickets/'.$ticket->id, 'local');
            if (! is_string($path)) {
                throw new \RuntimeException('Repair ticket image could not be saved.');
            }
            $paths[] = $path;
        }

        if ($paths !== ($ticket->images ?? [])) {
            $ticket->update(['images' => $paths]);
        }
    }
}
