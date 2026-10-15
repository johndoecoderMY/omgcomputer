<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\RepairTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    private const TIMEZONE = 'Asia/Kuala_Lumpur';

    private const SLOTS = [
        '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
    ];

    public function create(Request $request): View
    {
        $query = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $month = isset($query['month'])
            ? Carbon::createFromFormat('Y-m', $query['month'], self::TIMEZONE)->startOfMonth()
            : Carbon::now(self::TIMEZONE)->startOfMonth();
        $selectedDate = $query['date'] ?? Carbon::now(self::TIMEZONE)->toDateString();
        $calendarStart = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        $bookedCounts = $this->bookedCounts($month->copy()->startOfMonth(), $month->copy()->endOfMonth());
        $days = [];

        for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) {
            $date = $day->toDateString();
            $days[] = [
                'date' => $date,
                'inMonth' => $day->month === $month->month,
                'isToday' => $date === Carbon::now(self::TIMEZONE)->toDateString(),
                'isSelected' => $date === $selectedDate,
                'isBookable' => $day->gte(Carbon::now(self::TIMEZONE)->startOfDay())
                    && $day->dayOfWeek !== Carbon::SUNDAY
                    && ($bookedCounts[$date] ?? 0) < count(self::SLOTS),
                'bookedCount' => $bookedCounts[$date] ?? 0,
            ];
        }

        $selected = Carbon::createFromFormat('Y-m-d', $selectedDate, self::TIMEZONE);
        $services = collect(config('products.catalog'));

        return view('bookings.create', [
            'month' => $month,
            'days' => $days,
            'selectedDate' => $selected,
            'slots' => $this->availableSlots($selected),
            'services' => $services,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $start = Carbon::parse($data['start'], self::TIMEZONE)->setTimezone('UTC');
        $end = Carbon::parse($data['end'], self::TIMEZONE)->setTimezone('UTC');

        $events = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('starts_at', [$start, $end])
            ->get(['starts_at', 'duration_minutes'])
            ->map(fn (Booking $booking): array => [
                'title' => 'Unavailable',
                'start' => $booking->starts_at->setTimezone(self::TIMEZONE)->toIso8601String(),
                'display' => 'background',
                'color' => '#c94c4c',
            ]);

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

    public function store(Request $request): RedirectResponse
    {
        $serviceIds = collect(config('products.catalog'))->pluck('id')->all();
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:64'],
            'service_id' => ['required', 'string', Rule::in($serviceIds)],
            'device_model' => ['nullable', 'string', 'max:255'],
            'request_notes' => ['nullable', 'string', 'max:3000'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', Rule::in(self::SLOTS)],
        ]);
        $start = Carbon::createFromFormat(
            'Y-m-d H:i',
            $data['booking_date'] . ' ' . $data['start_time'],
            self::TIMEZONE,
        );
        if ($start->dayOfWeek === Carbon::SUNDAY || $start->lte(Carbon::now(self::TIMEZONE))) {
            throw ValidationException::withMessages(['start_time' => 'Choose a future appointment during business hours.']);
        }
        if (! in_array($data['start_time'], $this->availableSlots($start), true)) {
            throw ValidationException::withMessages(['start_time' => 'That appointment has just been taken. Choose another time.']);
        }

        $service = collect(config('products.catalog'))->firstWhere('id', $data['service_id']);

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
                    'reference' => 'BK-' . Str::upper(Str::random(10)),
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

        return redirect()->route('bookings.confirmation', $booking->reference);
    }

    public function confirmation(string $reference): View
    {
        $booking = Booking::query()->where('reference', $reference)->firstOrFail();

        return view('bookings.confirmation', compact('booking'));
    }

    public function index(Request $request): View
    {
        $status = $request->input('status');
        $query = Booking::query()->with(['assignee', 'repairTicket']);
        if (isset(Booking::STATUSES[$status])) {
            $query->where('status', $status);
        }

        return view('bookings.index', [
            'bookings' => $query->orderBy('starts_at')->paginate(30)->withQueryString(),
            'statuses' => Booking::STATUSES,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Booking::STATUSES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        DB::transaction(function () use ($booking, $data, $request): void {
            $booking = Booking::query()->with('customer')->lockForUpdate()->findOrFail($booking->id);
            $booking->update($data);

            if ($data['status'] === 'confirmed' && ! $booking->repair_ticket_id) {
                $ticket = RepairTicket::create([
                    'ticket_number' => 'TEMP-' . Str::upper(Str::random(12)),
                    'customer_id' => $booking->customer_id,
                    'project_id' => $booking->customer?->project_id,
                    'assigned_to' => $data['assigned_to'] ?? null,
                    'device_type' => 'Computer',
                    'device_model' => $booking->device_model,
                    'issue_description' => trim($booking->service_name . ".\n" . ($booking->request_notes ?? 'Appointment request converted to a repair ticket.')),
                    'status' => 'new',
                    'priority' => 'normal',
                    'received_at' => now(),
                ]);
                $ticket->update([
                    'ticket_number' => 'RT-' . now()->format('Y') . '-' . str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT),
                ]);
                $ticket->updates()->create([
                    'user_id' => $request->user()->id,
                    'title' => 'Created from confirmed booking ' . $booking->reference,
                    'status' => 'new',
                    'notes' => $booking->request_notes,
                ]);
                $booking->update(['repair_ticket_id' => $ticket->id]);
            }
        });

        return back()->with('success', 'Booking updated.');
    }

    private function availableSlots(Carbon $date): array
    {
        $localDate = $date->copy()->setTimezone(self::TIMEZONE)->startOfDay();
        if ($localDate->isSunday() || $localDate->lt(Carbon::now(self::TIMEZONE)->startOfDay())) {
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

        return array_values(array_filter(self::SLOTS, function (string $slot) use ($booked, $localDate, $now): bool {
            $slotTime = $localDate->copy()->setTimeFromTimeString($slot);

            return ! in_array($slot, $booked, true) && $slotTime->gt($now);
        }));
    }

    private function bookedCounts(Carbon $start, Carbon $end): array
    {
        $bookings = Booking::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('starts_at', [
                $start->copy()->setTimezone('UTC'),
                $end->copy()->endOfDay()->setTimezone('UTC'),
            ])
            ->get(['starts_at']);

        return $bookings->countBy(fn (Booking $booking): string => $booking->starts_at->setTimezone(self::TIMEZONE)->toDateString())->all();
    }
}