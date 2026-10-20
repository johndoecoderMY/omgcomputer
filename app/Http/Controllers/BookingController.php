<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\Customer;
use App\Models\ProductService;
use App\Models\RepairTicket;
use App\Models\User;
use App\Services\WorkspaceNotifier;
use App\Support\StatusBadge;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class BookingController extends Controller
{
    private const TIMEZONE = 'Asia/Kuala_Lumpur';

    public function create(Request $request): View
    {
        $selectedDate = Carbon::now(self::TIMEZONE)->toDateString();
        $selected = Carbon::createFromFormat('Y-m-d', $selectedDate, self::TIMEZONE);
        $services = ProductService::query()->orderBy('name')->get();

        return view('bookings.create', [
            'selectedDate' => $selected,
            'services' => $services,
        ]);
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $start = Carbon::parse($data['start'])->setTimezone(self::TIMEZONE)->startOfDay();
        $end = Carbon::parse($data['end'])->setTimezone(self::TIMEZONE)->startOfDay()->subDay();
        $startUtc = $start->copy()->setTimezone('UTC');
        $endUtc = $end->copy()->endOfDay()->setTimezone('UTC');
        $bookedSlots = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('starts_at', [$startUtc, $endUtc])
            ->get(['starts_at'])
            ->groupBy(fn (Booking $booking): string => $booking->starts_at->setTimezone(self::TIMEZONE)->toDateString())
            ->map(fn ($bookings) => $bookings
                ->map(fn (Booking $booking): string => $booking->starts_at->setTimezone(self::TIMEZONE)->format('H:i'))
                ->all());
        $slotsByDay = BookingTimeSlot::query()
            ->orderBy('start_time')
            ->get(['day_of_week', 'start_time'])
            ->groupBy('day_of_week')
            ->map(fn ($slots) => $slots->map(fn (BookingTimeSlot $slot): string => substr($slot->start_time, 0, 5))->all());
        $now = Carbon::now(self::TIMEZONE);
        $events = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateString = $date->toDateString();
            $booked = $bookedSlots->get($dateString, []);
            $hasAvailability = collect($slotsByDay->get($date->dayOfWeek, []))
                ->contains(fn (string $slot): bool => ! in_array($slot, $booked, true)
                    && $date->copy()->setTimeFromTimeString($slot)->gt($now));

            if ($hasAvailability) {
                $events[] = [
                    'title' => 'Available',
                    'start' => $dateString,
                    'allDay' => true,
                    'display' => 'background',
                    'color' => '#d9f8eb',
                    'extendedProps' => ['available' => true],
                ];
            }
        }

        return response()->json($events);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $date = Carbon::createFromFormat('Y-m-d', $data['date'], self::TIMEZONE);

        return response()->json([
            'date' => $date->toDateString(),
            'slots' => $this->availableSlots($date),
        ]);
    }

    public function store(Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        $serviceIds = ProductService::query()->pluck('id')->all();
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:64'],
            'service_id' => ['required', 'string', Rule::in($serviceIds)],
            'device_model' => ['nullable', 'string', 'max:255'],
            'request_notes' => ['nullable', 'string', 'max:3000'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);
        $time = $request->validate([
            'start_time' => [
                'required',
                Rule::in($this->availableSlots(Carbon::createFromFormat('Y-m-d', $data['booking_date'], self::TIMEZONE))),
            ],
        ]);
        $data = [...$data, ...$time];
        $start = Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['booking_date'].' '.$data['start_time'],
            self::TIMEZONE,
        );
        if ($start->dayOfWeek === Carbon::SUNDAY || $start->lte(Carbon::now(self::TIMEZONE))) {
            throw ValidationException::withMessages(['start_time' => 'Choose a future appointment during business hours.']);
        }
        if (! in_array($data['start_time'], $this->availableSlots($start), true)) {
            throw ValidationException::withMessages(['start_time' => 'That appointment has just been taken. Choose another time.']);
        }

        $service = ProductService::query()->findOrFail($data['service_id']);

        try {
            $booking = DB::transaction(function () use ($data, $service, $start): Booking {
                $customer = Customer::query()->where('email', $data['customer_email'])->first();
                if (! $customer) {
                    $customer = Customer::create([
                        'name' => $data['customer_name'],
                        'email' => $data['customer_email'],
                        'phone' => $data['customer_phone'],
                    ]);
                }

                return Booking::create([
                    'reference' => 'BK-'.Str::upper(Str::random(10)),
                    'customer_id' => $customer->id,
                    'customer_name' => $data['customer_name'],
                    'customer_email' => $data['customer_email'],
                    'customer_phone' => $data['customer_phone'],
                    'service_id' => $service['id'],
                    'service_name' => $service['name'],
                    'device_model' => $data['device_model'] ?? null,
                    'request_notes' => $data['request_notes'] ?? null,
                    'starts_at' => $start->copy()->setTimezone('UTC'),
                    'duration_minutes' => 60,
                    'status' => 'pending',
                ]);
            });
        } catch (QueryException $exception) {
            if (Booking::query()->where('starts_at', $start->copy()->setTimezone('UTC'))->exists()) {
                throw ValidationException::withMessages(['start_time' => 'That appointment has just been taken. Choose another time.']);
            }

            throw $exception;
        }
        $notifier->notifyAdmins(
            null,
            'New booking received',
            sprintf('%s booked %s for %s.', $booking->customer_name, $booking->service_name, $booking->starts_at->setTimezone(self::TIMEZONE)->format('M j, Y g:i A')),
            route('bookings.index'),
        );

        return redirect()->route('bookings.confirmation', $booking->reference);
    }

    public function confirmation(string $reference): View
    {
        $booking = Booking::query()->where('reference', $reference)->firstOrFail();

        return view('bookings.confirmation', compact('booking'));
    }

    public function index(Request $request): View
    {
        return view('bookings.index', [
            'statuses' => Booking::STATUSES,
            'customerView' => request()->user()->hasRole('customer'),
        ]);
    }

    public function datatable(Request $request)
    {
        $query = Booking::query()->with(['assignee', 'repairTicket']);
        if ($request->user()->hasRole('customer')) {
            $query->whereRaw('LOWER(TRIM(customer_email)) = ?', [strtolower(trim($request->user()->email))]);
        }
        $status = $request->input('status');
        if (is_string($status) && isset(Booking::STATUSES[$status])) {
            $query->where('status', $status);
        }
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return DataTables::eloquent($query)
            ->filter(function (Builder $bookings) use ($request): void {
                $search = trim((string) $request->input('search.value', ''));
                if ($search !== '') {
                    $bookings->where(fn (Builder $matches) => $matches
                        ->where('reference', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('service_name', 'like', "%{$search}%")
                        ->orWhere('device_model', 'like', "%{$search}%")
                        ->orWhere('request_notes', 'like', "%{$search}%"));
                }
            }, true)
            ->editColumn('starts_at', fn (Booking $booking): string => $booking->starts_at
                ->setTimezone(self::TIMEZONE)->format('M j, Y g:i A'))
            ->addColumn('customer_contact', fn (Booking $booking): string => sprintf(
                '%s<div class="small text-body-secondary">%s · %s</div>',
                e($booking->customer_name),
                e($booking->customer_email),
                e($booking->customer_phone),
            ))
            ->addColumn('service', fn (Booking $booking): string => sprintf(
                '%s<div class="small text-body-secondary">%s</div>%s',
                e($booking->service_name),
                e($booking->device_model),
                $booking->repairTicket
                    ? sprintf('<a class="small d-block mt-1" href="%s">Ticket %s</a>', e(route('tickets.show', $booking->repairTicket)), e($booking->repairTicket->ticket_number))
                    : '',
            ))
            ->editColumn('request_notes', fn (Booking $booking): string => e($booking->request_notes ?: '—'))
            ->addColumn('status_label', fn (Booking $booking): string => (string) StatusBadge::make(
                $booking->status,
                Booking::STATUSES[$booking->status] ?? $booking->status,
            ))
            ->addColumn('management', function (Booking $booking) use ($users, $request): string {
                if ($request->user()->hasRole('customer')) {
                    return '';
                }
                $statusOptions = collect(Booking::STATUSES)->map(fn (string $label, string $value): string => sprintf(
                    '<option value="%s"%s>%s</option>',
                    e($value),
                    $booking->status === $value ? ' selected' : '',
                    e($label),
                ))->implode('');
                $userOptions = '<option value="">Unassigned</option>'.$users->map(fn (User $user): string => sprintf(
                    '<option value="%d"%s>%s</option>',
                    $user->id,
                    $booking->assigned_to === $user->id ? ' selected' : '',
                    e($user->name),
                ))->implode('');

                return sprintf(
                    '<form class="d-flex flex-column gap-2" method="POST" action="%s"><input type="hidden" name="_token" value="%s"><input type="hidden" name="_method" value="PUT"><select class="form-select form-select-sm" name="status" aria-label="Booking status">%s</select><select class="form-select form-select-sm" name="assigned_to" aria-label="Assigned staff">%s</select><button class="btn btn-sm btn-outline-primary">Save</button></form>',
                    e(route('bookings.update', $booking)),
                    e(csrf_token()),
                    $statusOptions,
                    $userOptions,
                );
            })
            ->rawColumns(['customer_contact', 'service', 'status_label', 'management'])
            ->toJson();
    }

    public function update(Request $request, Booking $booking, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Booking::STATUSES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $previousAssigneeId = $booking->assigned_to;
        $previousStatus = $booking->status;
        DB::transaction(function () use ($booking, $data, $request): void {
            $booking = Booking::query()->with('customer')->lockForUpdate()->findOrFail($booking->id);
            $booking->update($data);

            if ($data['status'] === 'confirmed' && ! $booking->repair_ticket_id) {
                $ticket = RepairTicket::create([
                    'ticket_number' => 'TEMP-'.Str::upper(Str::random(12)),
                    'customer_id' => $booking->customer_id,
                    'project_id' => $booking->customer?->project_id,
                    'assigned_to' => $data['assigned_to'] ?? null,
                    'created_by_user_id' => $request->user()->id,
                    'device_type' => 'Computer',
                    'device_model' => $booking->device_model,
                    'issue_description' => trim($booking->service_name.".\n".($booking->request_notes ?? 'Appointment request converted to a repair ticket.')),
                    'status' => 'new',
                    'priority' => 'normal',
                    'received_at' => now(),
                ]);
                $ticket->update([
                    'ticket_number' => 'RT-'.now()->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
                ]);
                $ticket->updates()->create([
                    'user_id' => $request->user()->id,
                    'title' => 'Created from confirmed booking '.$booking->reference,
                    'status' => 'new',
                    'notes' => $booking->request_notes,
                ]);
                $booking->update(['repair_ticket_id' => $ticket->id]);
            }
        });
        $booking->refresh();
        $notifier->notifyAdmins(
            $request->user(),
            'Booking updated',
            sprintf('Booking %s for %s was updated to %s.', $booking->reference, $booking->customer_name, Booking::STATUSES[$booking->status]),
            route('bookings.index'),
        );
        if ($booking->assigned_to && (
            (int) $booking->assigned_to !== (int) $previousAssigneeId
            || $booking->status !== $previousStatus
        )) {
            $notifier->notifyUsers(
                User::query()->whereKey($booking->assigned_to)->get(),
                $request->user(),
                'Booking assigned to you',
                sprintf('Booking %s for %s is assigned to you.', $booking->reference, $booking->customer_name),
                route('bookings.index'),
            );
        }

        return back()->with('success', 'Booking updated.');
    }

    private function availableSlots(Carbon $date): array
    {
        $localDate = $date->copy()->setTimezone(self::TIMEZONE)->startOfDay();
        if ($localDate->lt(Carbon::now(self::TIMEZONE)->startOfDay())) {
            return [];
        }

        $slots = BookingTimeSlot::query()
            ->where('day_of_week', $localDate->dayOfWeek)
            ->orderBy('start_time')
            ->pluck('start_time')
            ->map(fn (string $time): string => substr($time, 0, 5))
            ->all();
        if ($slots === []) {
            return [];
        }

        $startUtc = $localDate->copy()->setTimezone('UTC');
        $endUtc = $localDate->copy()->endOfDay()->setTimezone('UTC');
        $booked = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('starts_at', [$startUtc, $endUtc])
            ->get(['starts_at'])
            ->map(fn (Booking $booking): string => $booking->starts_at->setTimezone(self::TIMEZONE)->format('H:i'))
            ->all();
        $now = Carbon::now(self::TIMEZONE);

        return array_values(array_filter($slots, function (string $slot) use ($booked, $localDate, $now): bool {
            $slotTime = $localDate->copy()->setTimeFromTimeString($slot);

            return ! in_array($slot, $booked, true) && $slotTime->gt($now);
        }));
    }
}
