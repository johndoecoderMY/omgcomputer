<?php

namespace App\Http\Controllers;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\Customer;
use App\Models\ProductService;
use App\Models\RepairTicket;
use App\Models\User;
use App\Rules\AssignableUser;
use App\Services\WorkspaceNotifier;
use App\Support\AssignmentAccess;
use App\Support\DataTableQueryBuilder;
use App\Support\StatusBadge;
use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class BookingController extends Controller
{
    private const TIMEZONE = 'Asia/Kuala_Lumpur';

    private const BOOKABLE_SERVICES = ['basic-troubleshooting', 'fyp-consultation'];

    private const KAMPAR_CENTER = [4.326, 101.15];

    private const KAMPAR_SERVICE_RADIUS_KM = 7;

    public function create(Request $request): View
    {
        return $this->bookingForm($request, false);
    }

    public function createAuthenticated(Request $request): View
    {
        return $this->bookingForm($request, true);
    }

    private function bookingForm(Request $request, bool $authenticatedBooking): View
    {
        $services = ProductService::query()
            ->where('is_active', true)
            ->whereIn('id', self::BOOKABLE_SERVICES)
            ->orderBy('name')
            ->get();

        return view('bookings.create', [
            'services' => $services,
            'authenticatedBooking' => $authenticatedBooking,
            'deviceDetailsServiceId' => 'basic-troubleshooting',
            'serviceAreaCenter' => self::KAMPAR_CENTER,
            'serviceAreaRadiusKm' => self::KAMPAR_SERVICE_RADIUS_KM,
            // FullCalendar runs in UTC mode so these wall-clock values stay in MYT without a timezone plugin.
            'calendarNow' => Carbon::now(self::TIMEZONE)->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    public function events(Request $request)
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $start = Carbon::createFromFormat('Y-m-d', substr($data['start'], 0, 10), self::TIMEZONE)->startOfDay();
        $end = Carbon::createFromFormat('Y-m-d', substr($data['end'], 0, 10), self::TIMEZONE)->startOfDay()->subDay();
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
                    'start' => $dateString,
                    'allDay' => true,
                    'display' => 'background',
                    'color' => '#dcfce7',
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

    public function reverseGeocode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
        if (! Cache::add('booking.reverse-geocode.request-lock', true, now()->addSecond())) {
            return response()->json(['message' => 'Please wait before looking up another address.'], 429);
        }

        $request = Http::acceptJson()
            ->withUserAgent(config('app.name').' booking address lookup ('.config('app.url').')')
            ->timeout(5);
        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
            $request->withOptions([
                'curl' => [CURLOPT_SSL_OPTIONS => CURLSSLOPT_NATIVE_CA],
            ]);
        }

        try {
            $response = $request->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'jsonv2',
                'lat' => $data['latitude'],
                'lon' => $data['longitude'],
                'zoom' => 18,
                'addressdetails' => 1,
            ]);
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('Booking pin address lookup could not connect to OpenStreetMap.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Address lookup is temporarily unavailable.'], 503);
        }

        if (! $response->successful()) {
            Log::warning('Booking pin address lookup returned an unsuccessful response.', [
                'status' => $response->status(),
            ]);

            return response()->json(['message' => 'Address lookup is temporarily unavailable.'], 503);
        }

        $address = trim((string) $response->json('display_name'));
        if ($address === '') {
            return response()->json(['message' => 'No address was found for this pin.'], 404);
        }

        return response()->json(['address' => $address]);
    }

    public function store(Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:64'],
            'service_id' => [
                'required',
                'string',
                Rule::exists('product_services', 'id')->where(fn ($query) => $query
                    ->where('is_active', true)
                    ->whereIn('id', self::BOOKABLE_SERVICES)),
            ],
            'device_model' => ['exclude_unless:service_id,basic-troubleshooting', 'nullable', 'string', 'max:255'],
            'request_notes' => ['exclude_unless:service_id,basic-troubleshooting', 'nullable', 'string', 'max:3000'],
            'customer_dropoff_at_shop' => ['exclude_unless:service_id,basic-troubleshooting', 'sometimes', 'boolean'],
            'pickup_address' => ['exclude_unless:service_id,basic-troubleshooting', 'exclude_if:customer_dropoff_at_shop,1', 'nullable', 'string', 'max:1000'],
            'dropoff_address' => ['exclude_unless:service_id,basic-troubleshooting', 'exclude_if:customer_dropoff_at_shop,1', 'nullable', 'string', 'max:1000'],
            'dropoff_same_as_pickup' => ['exclude_unless:service_id,basic-troubleshooting', 'exclude_if:customer_dropoff_at_shop,1', 'sometimes', 'boolean'],
            'pickup_latitude' => ['exclude_unless:service_id,basic-troubleshooting', 'exclude_if:customer_dropoff_at_shop,1', 'nullable', 'numeric', 'between:-90,90', 'required_with:pickup_longitude'],
            'pickup_longitude' => ['exclude_unless:service_id,basic-troubleshooting', 'exclude_if:customer_dropoff_at_shop,1', 'nullable', 'numeric', 'between:-180,180', 'required_with:pickup_latitude'],
            'dropoff_latitude' => ['exclude_unless:service_id,basic-troubleshooting', 'exclude_if:customer_dropoff_at_shop,1', 'nullable', 'numeric', 'between:-90,90', 'required_with:dropoff_longitude'],
            'dropoff_longitude' => ['exclude_unless:service_id,basic-troubleshooting', 'exclude_if:customer_dropoff_at_shop,1', 'nullable', 'numeric', 'between:-180,180', 'required_with:dropoff_latitude'],
            'booking_date' => ['required', 'date_format:Y-m-d'],
        ]);
        $customerDropoffAtShop = $data['service_id'] === 'basic-troubleshooting'
            && ($data['customer_dropoff_at_shop'] ?? false);
        if (! $customerDropoffAtShop && $data['service_id'] === 'basic-troubleshooting' && ($data['dropoff_same_as_pickup'] ?? false)) {
            $data['dropoff_address'] = $data['pickup_address'] ?? null;
            $data['dropoff_latitude'] = $data['pickup_latitude'] ?? null;
            $data['dropoff_longitude'] = $data['pickup_longitude'] ?? null;
        }
        if (! $customerDropoffAtShop && $data['service_id'] === 'basic-troubleshooting') {
            foreach (['pickup', 'dropoff'] as $location) {
                $latitude = $data[$location.'_latitude'] ?? null;
                $longitude = $data[$location.'_longitude'] ?? null;
                $address = trim((string) ($data[$location.'_address'] ?? ''));
                $locationLabel = $location === 'pickup' ? 'Pickup' : 'Drop-off';
                if ($address !== '' && ($latitude === null || $longitude === null)) {
                    throw ValidationException::withMessages([
                        $location.'_address' => __(
                            ':location address needs a map pin so we can confirm it is within Kampar.',
                            ['location' => $locationLabel],
                        ),
                    ]);
                }
                if ($latitude !== null && $longitude !== null
                    && ! $this->isWithinKamparServiceArea((float) $latitude, (float) $longitude)) {
                    throw ValidationException::withMessages([
                        $location.'_latitude' => $locationLabel.' pin must be within the Kampar service area.',
                    ]);
                }
            }
        }
        $bookingDate = Carbon::createFromFormat('Y-m-d', $data['booking_date'], self::TIMEZONE)->startOfDay();
        if ($bookingDate->lt(Carbon::today(self::TIMEZONE))) {
            throw ValidationException::withMessages(['booking_date' => 'Choose a future appointment date.']);
        }
        $time = $request->validate([
            'start_time' => [
                'required',
                Rule::in($this->availableSlots($bookingDate)),
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

        $service = ProductService::query()
            ->where('is_active', true)
            ->findOrFail($data['service_id']);

        try {
            $booking = DB::transaction(function () use ($data, $service, $start, $customerDropoffAtShop): Booking {
                $customer = Customer::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($data['customer_email']))])
                    ->orderBy('id')
                    ->first();
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
                    'customer_dropoff_at_shop' => $customerDropoffAtShop,
                    'pickup_address' => $data['pickup_address'] ?? null,
                    'dropoff_address' => $data['dropoff_address'] ?? null,
                    'pickup_latitude' => $data['pickup_latitude'] ?? null,
                    'pickup_longitude' => $data['pickup_longitude'] ?? null,
                    'dropoff_latitude' => $data['dropoff_latitude'] ?? null,
                    'dropoff_longitude' => $data['dropoff_longitude'] ?? null,
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
            sprintf('%s booked %s for %s MYT.', $booking->customer_name, $booking->service_name, $booking->starts_at->setTimezone(self::TIMEZONE)->format('M j, Y g:i A')),
            route('bookings.index'),
        );

        return redirect()->route('bookings.confirmation', $booking->reference);
    }

    public function storeAuthenticated(Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        $request->merge([
            'customer_name' => $request->user()->name,
            'customer_email' => $request->user()->email,
        ]);

        return $this->store($request, $notifier);
    }

    public function confirmation(string $reference): View
    {
        $booking = Booking::query()->where('reference', $reference)->firstOrFail();

        return view('bookings.confirmation', compact('booking'));
    }

    public function index(Request $request): View
    {
        $services = Booking::query()
            ->when($request->user()->hasRole('customer'), fn (Builder $bookings) => $bookings
                ->whereRaw('LOWER(TRIM(customer_email)) = ?', [strtolower(trim($request->user()->email))]))
            ->whereNotNull('service_name')
            ->distinct()
            ->orderBy('service_name')
            ->pluck('service_name');

        return view('bookings.index', [
            'statuses' => Booking::STATUSES,
            'editableStatuses' => Booking::EDITABLE_STATUSES,
            'customerView' => request()->user()->hasRole('customer'),
            'services' => $services,
            'users' => $request->user()->hasRole('admin')
                ? AssignmentAccess::users()->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function calendar(Request $request): View
    {
        return view('bookings.calendar', [
            'customerView' => $request->user()->hasRole('customer'),
            'calendarNow' => Carbon::now(self::TIMEZONE)->format('Y-m-d\TH:i:s\Z'),
        ]);
    }

    public function show(Request $request, Booking $booking): View
    {
        if ($request->user()->hasRole('customer')) {
            abort_unless(
                strtolower(trim($booking->customer_email)) === strtolower(trim($request->user()->email)),
                404,
            );
        }
        $booking->load(['customer', 'repairTicket']);

        return view('bookings.show', compact('booking'));
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);
        $start = Carbon::createFromFormat('Y-m-d', substr($data['start'], 0, 10), self::TIMEZONE)->startOfDay()->setTimezone('UTC');
        $end = Carbon::createFromFormat('Y-m-d', substr($data['end'], 0, 10), self::TIMEZONE)->startOfDay()->setTimezone('UTC');
        $query = Booking::query()
            ->with('customer')
            ->where('starts_at', '<', $end)
            ->where('starts_at', '>=', $start)
            ->whereIn('status', ['pending', 'confirmed', 'completed', 'cancelled'])
            ->orderBy('starts_at');

        if ($request->user()->hasRole('customer')) {
            $query->whereRaw('LOWER(TRIM(customer_email)) = ?', [strtolower(trim($request->user()->email))]);
        }

        $events = $query->get()->map(function (Booking $booking) use ($request): array {
            $localStart = $booking->starts_at->copy()->setTimezone(self::TIMEZONE);

            return [
                'id' => $booking->id,
                'title' => $booking->customer_name.' · '.$booking->service_name,
                // Encode Malaysia wall time as UTC for FullCalendar's fixed UTC display mode.
                'start' => $localStart->format('Y-m-d\TH:i:s\Z'),
                'end' => $localStart->copy()->addMinutes($booking->duration_minutes)->format('Y-m-d\TH:i:s\Z'),
                'backgroundColor' => Booking::CALENDAR_COLORS[$booking->status]['background'] ?? Booking::CALENDAR_COLORS['pending']['background'],
                'borderColor' => Booking::CALENDAR_COLORS[$booking->status]['background'] ?? Booking::CALENDAR_COLORS['pending']['background'],
                'textColor' => Booking::CALENDAR_COLORS[$booking->status]['text'] ?? Booking::CALENDAR_COLORS['pending']['text'],
                'url' => $request->user()->hasRole('customer') ? null : route('bookings.index'),
                'extendedProps' => [
                    'kind' => 'booking',
                    'reference' => $booking->reference,
                    'status' => Booking::STATUSES[$booking->status] ?? $booking->status,
                    'customer' => $booking->customer_name,
                    'email' => $booking->customer_email,
                    'phone' => $booking->customer_phone,
                    'service' => $booking->service_name,
                    'device' => $booking->device_model,
                    'notes' => $booking->request_notes,
                    'booking_url' => route('bookings.show', $booking),
                    'customer_url' => ! $request->user()->hasRole('customer') && $booking->customer
                        ? route('customers.show', $booking->customer)
                        : null,
                ],
            ];
        });

        return response()->json($events);
    }

    public function datatable(Request $request)
    {
        $query = Booking::query()->with(['assignee', 'repairTicket', 'customer']);
        if ($request->user()->hasRole('customer')) {
            $query->whereRaw('LOWER(TRIM(customer_email)) = ?', [strtolower(trim($request->user()->email))]);
        }
        $filterValues = $request->query('filter', []);
        if (! is_array($filterValues)) {
            $filterValues = [];
        }
        foreach (['status', 'service', 'customer_name', 'starts_from', 'starts_to'] as $filter) {
            if (! array_key_exists($filter, $filterValues) && $request->query->has($filter)) {
                $filterValues[$filter] = $request->query($filter);
            }
        }
        $filterValues = Validator::make(
            ['filter' => $filterValues],
            [
                'filter' => ['array'],
                'filter.status' => ['nullable', 'string', Rule::in(array_keys(Booking::STATUSES))],
                'filter.service' => ['nullable', 'string', 'max:255'],
                'filter.customer_name' => ['nullable', 'string', 'max:255'],
                'filter.starts_from' => ['nullable', 'date'],
                'filter.starts_to' => ['nullable', 'date', 'after_or_equal:filter.starts_from'],
            ],
        )->validate()['filter'] ?? [];
        $queryRequest = Request::createFrom($request);
        $queryRequest->query->set('filter', $filterValues);
        return DataTables::eloquent(DataTableQueryBuilder::for($query, $queryRequest, [
            'status' => AllowedFilter::exact('status'),
            'service' => AllowedFilter::exact('service', 'service_name'),
            'customer_name' => AllowedFilter::partial('customer_name'),
            'starts_from' => AllowedFilter::callback('starts_from', fn (Builder $bookings, string $value) => $bookings->where(
                'starts_at',
                '>=',
                Carbon::parse($value, self::TIMEZONE)->setTimezone('UTC'),
            )),
            'starts_to' => AllowedFilter::callback('starts_to', fn (Builder $bookings, string $value) => $bookings->where(
                'starts_at',
                '<=',
                Carbon::parse($value, self::TIMEZONE)->setTimezone('UTC'),
            )),
        ]))
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
                ->setTimezone(self::TIMEZONE)->format('M j, Y g:i A').' MYT')
            ->addColumn('customer_contact', function (Booking $booking) use ($request): string {
                $customerName = e($booking->customer_name);
                if (! $request->user()->hasRole('customer') && $booking->customer) {
                    $customerName = sprintf(
                        '<a href="%s">%s</a>',
                        e(route('customers.show', $booking->customer)),
                        $customerName,
                    );
                }

                return sprintf(
                    '%s<div class="small text-body-secondary">%s · %s</div>',
                    $customerName,
                    e($booking->customer_email),
                    e($booking->customer_phone),
                );
            })
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
            ->addColumn('management', function (Booking $booking) use ($request): string {
                if ($request->user()->hasRole('customer')) {
                    return '';
                }
                $assignedTo = $request->user()->hasRole('admin')
                    ? (string) $booking->assigned_to
                    : '';

                return sprintf(
                    '<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#booking-status-modal" data-booking-update data-booking-update-url="%s" data-booking-status="%s" data-booking-assigned-to="%s" data-booking-reference="%s" data-booking-cancellation-reason="%s">Update</button>',
                    e(route('bookings.update', $booking)),
                    e($booking->status),
                    e($assignedTo),
                    e($booking->reference),
                    e($booking->cancellation_reason ?? ''),
                );
            })
            ->rawColumns(['customer_contact', 'service', 'status_label', 'management'])
            ->toJson();
    }

    public function update(Request $request, Booking $booking, WorkspaceNotifier $notifier): RedirectResponse|JsonResponse
    {
        AssignmentAccess::preventNonAdminAssignment($request, ['assigned_to']);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Booking::EDITABLE_STATUSES))],
            'assigned_to' => ['nullable', 'integer', new AssignableUser],
            'cancellation_reason' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['status'] !== 'cancelled') {
            $data['cancellation_reason'] = null;
        }
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
                    'assigned_to' => $booking->assigned_to,
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
        $customerEmail = trim((string) $booking->customer_email);
        if ($previousStatus !== 'confirmed'
            && $booking->status === 'confirmed'
            && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            Mail::to($customerEmail)->send(new BookingConfirmedMail($booking));
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Booking updated.']);
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

    private function isWithinKamparServiceArea(float $latitude, float $longitude): bool
    {
        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($latitude - self::KAMPAR_CENTER[0]);
        $longitudeDelta = deg2rad($longitude - self::KAMPAR_CENTER[1]);
        $startLatitude = deg2rad(self::KAMPAR_CENTER[0]);
        $endLatitude = deg2rad($latitude);
        $distanceFactor = sin($latitudeDelta / 2) ** 2
            + cos($startLatitude) * cos($endLatitude) * sin($longitudeDelta / 2) ** 2;
        $distanceFactor = min(1.0, max(0.0, $distanceFactor));
        $distanceKm = 2 * $earthRadiusKm * atan2(sqrt($distanceFactor), sqrt(1 - $distanceFactor));

        return $distanceKm <= self::KAMPAR_SERVICE_RADIUS_KM;
    }
}
