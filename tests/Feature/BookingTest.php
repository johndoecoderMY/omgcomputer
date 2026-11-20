<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\Customer;
use App\Models\ProductService;
use App\Models\RepairTicket;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_is_emailed_when_booking_is_confirmed_once(): void
    {
        Mail::fake();
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Booking Customer',
            'email' => 'booking-customer@example.test',
            'phone' => '+60112223333',
        ]);
        $booking = Booking::query()->create([
            'reference' => 'BK-CONFIRM-EMAIL',
            'customer_id' => $customer->id,
            'customer_name' => 'Booking Customer',
            'customer_email' => 'booking-customer@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'service_name' => 'Computer/PC Troubleshooting',
            'starts_at' => Carbon::now('UTC')->addDays(2),
            'duration_minutes' => 60,
            'status' => 'pending',
        ]);

        $this->actingAs($staff)->put(route('bookings.update', $booking), [
            'status' => 'confirmed',
        ])->assertSessionHas('success', 'Booking updated.');

        $ticketNumber = $booking->fresh()->repairTicket->ticket_number;

        Mail::assertSent(BookingConfirmedMail::class, fn (BookingConfirmedMail $mail): bool => $mail->hasTo('booking-customer@example.test')
            && $mail->booking->reference === 'BK-CONFIRM-EMAIL'
            && str_contains($mail->render(), 'Your booking is confirmed')
            && str_contains($mail->render(), 'Computer/PC Troubleshooting')
            && str_contains($mail->render(), $ticketNumber)
            && str_contains($mail->render(), route('repairs.track.show', $ticketNumber))
            && str_contains($mail->render(), 'mailto:support@omgcomputer.my')
            && str_contains($mail->render(), 'https://wa.me/60104224350?text='));

        $this->actingAs($staff)->put(route('bookings.update', $booking), [
            'status' => 'confirmed',
        ])->assertSessionHas('success', 'Booking updated.');

        Mail::assertSent(BookingConfirmedMail::class, 1);
    }

    public function test_booking_can_be_updated_with_json_for_the_ajax_status_modal(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Booking Customer',
            'email' => 'booking-customer@example.test',
            'phone' => '+60112223333',
        ]);
        $booking = Booking::query()->create([
            'reference' => 'BK-AJAX-UPDATE',
            'customer_id' => $customer->id,
            'customer_name' => 'Booking Customer',
            'customer_email' => 'booking-customer@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'service_name' => 'Computer/PC Troubleshooting',
            'starts_at' => Carbon::now('UTC')->addDays(2),
            'duration_minutes' => 60,
            'status' => 'pending',
        ]);

        $this->actingAs($staff)->putJson(route('bookings.update', $booking), [
            'status' => 'completed',
            'assigned_to' => $staff->id,
        ])->assertOk()->assertJson(['message' => 'Booking updated.']);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'completed',
            'assigned_to' => $staff->id,
        ]);
    }

    public function test_staff_booking_list_renders_the_status_update_modal(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');

        $this->actingAs($staff)->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('<title>Bookings | OMG Computer</title>', false)
            ->assertSee('id="booking-status-modal"', false)
            ->assertSee('id="booking-status-form"', false)
            ->assertSee('id="booking-assigned-to"', false)
            ->assertSee('Save changes');
    }

    public function test_booking_confirmation_skips_email_when_customer_address_is_missing_or_invalid(): void
    {
        Mail::fake();
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Booking Customer',
            'email' => 'booking-customer@example.test',
            'phone' => '+60112223333',
        ]);

        foreach (['', 'not-an-email'] as $index => $email) {
            $booking = Booking::query()->create([
                'reference' => 'BK-NO-EMAIL-'.$index,
                'customer_id' => $customer->id,
                'customer_name' => 'Booking Customer',
                'customer_email' => $email,
                'customer_phone' => '+60112223333',
                'service_id' => 'basic-troubleshooting',
                'service_name' => 'Computer/PC Troubleshooting',
                'starts_at' => Carbon::now('UTC')->addDays($index + 2),
                'duration_minutes' => 60,
                'status' => 'pending',
            ]);

            $this->actingAs($staff)->put(route('bookings.update', $booking), [
                'status' => 'confirmed',
            ])->assertSessionHas('success', 'Booking updated.');
        }

        Mail::assertNothingSent();
    }

    public function test_public_booking_lists_only_the_two_enabled_customer_services(): void
    {
        ProductService::query()->whereKey('laptop-cleaning')->update(['is_active' => true]);

        $this->get(route('bookings.create'))
            ->assertOk()
            ->assertSee('booking-help-section', false)
            ->assertSee('Not sure what to pick?')
            ->assertSee('We’ll help you find the right service.')
            ->assertSee('Just contact us')
            ->assertSee('Email us')
            ->assertSee('Message us on WhatsApp')
            ->assertDontSee('Choose a booking time')
            ->assertDontSee('Ask us on WhatsApp')
            ->assertSee('Computer/PC Troubleshooting')
            ->assertSee('FYP Consultation')
            ->assertDontSee('Laptop cleaning')
            ->assertSee('Fill address from pin')
            ->assertSee('pin coordinates are sent to OpenStreetMap')
            ->assertViewHas('services', fn ($services): bool => $services->pluck('id')->all() === [
                'basic-troubleshooting',
                'fyp-consultation',
            ]);
    }

    public function test_booking_rejects_services_outside_the_two_customer_booking_options(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        $this->from(route('bookings.create'))
            ->post(route('bookings.store'), [
                'customer_name' => 'Booking customer',
                'customer_email' => 'booking@example.test',
                'customer_phone' => '+60112223333',
                'service_id' => 'laptop-cleaning',
                'booking_date' => $date->toDateString(),
            ])
            ->assertRedirect(route('bookings.create'))
            ->assertSessionHasErrors('service_id');
    }

    public function test_booking_pin_address_lookup_only_runs_when_requested(): void
    {
        Cache::forget('booking.reverse-geocode.request-lock');
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Kampar, Perak, Malaysia',
            ]),
        ]);

        $this->postJson(route('bookings.reverse-geocode'), [
            'latitude' => 4.326,
            'longitude' => 101.15,
        ])->assertOk()
            ->assertJsonPath('address', 'Kampar, Perak, Malaysia');

        Http::assertSent(fn (\Illuminate\Http\Client\Request $request): bool => str_contains(
            $request->url(),
            'nominatim.openstreetmap.org/reverse',
        ) && $request->hasHeader('User-Agent'));
    }

    public function test_booking_page_and_calendar_messages_follow_the_selected_public_language(): void
    {
        $this->withSession(['public_locale' => 'ms'])
            ->get(route('bookings.create'))
            ->assertOk()
            ->assertSee('Pilih tarikh')
            ->assertSee('Pilih tarikh pada kalendar')
            ->assertSee('Pilih tarikh dahulu')
            ->assertSee('Alamat pengambilan')
            ->assertSee('Alamat penghantaran semula')
            ->assertSee('Memuatkan masa yang tersedia...')
            ->assertSee('Pilih masa yang tersedia.')
            ->assertSee('Tiada masa tersedia pada tarikh ini.');

        $this->withSession(['public_locale' => 'zh_CN'])
            ->get(route('bookings.create'))
            ->assertOk()
            ->assertSee('选择日期')
            ->assertSee('请在日历中选择日期')
            ->assertSee('请先选择日期')
            ->assertSee('取件地址')
            ->assertSee('送回地址')
            ->assertSee('正在加载可预约时间')
            ->assertSee('请选择可预约时段。')
            ->assertSee('该日期没有可预约时段。');
    }

    public function test_customer_can_book_a_slot_and_the_same_time_cannot_be_booked_twice(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }
        $dateString = $date->toDateString();
        $this->get(route('bookings.create', ['date' => $dateString]))
            ->assertOk()
            ->assertSee('id="calendar"', false)
            ->assertSee('Book an appointment')
            ->assertSee('guestNavbar', false)
            ->assertSee('Book a service')
            ->assertSee('booking-help-section', false)
            ->assertSee('Not sure what to pick?')
            ->assertSee('Just contact us')
            ->assertSee('<div class="d-flex justify-content-end">', false)
            ->assertSee('<button class="btn btn-primary" type="submit">', false)
            ->assertSee('id="customer-email" type="email"', false)
            ->assertSee('name="customer_email"', false)
            ->assertSee('required autocomplete="email"', false)
            ->assertSee('OMG Computer')
            ->assertSee('Computer/PC Troubleshooting')
            ->assertSee('FYP Consultation')
            ->assertDontSee('Laptop cleaning')
            ->assertSee('id="device-details"', false)
            ->assertSee('id="pickup-address"', false)
            ->assertSee('id="dropoff-address"', false)
            ->assertSee('hidden', false);

        $data = [
            'customer_name' => 'Morgan Lee',
            'customer_email' => 'morgan@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'device_model' => 'Dell XPS 13',
            'pickup_address' => '12 Jalan Test, Kampar',
            'dropoff_address' => 'Unit 4, Example Apartments, Kampar',
            'request_notes' => 'Fan is noisy.',
            'booking_date' => $dateString,
            'start_time' => '11:00',
        ];
        $this->from(route('bookings.create', ['date' => $dateString]))
            ->post(route('bookings.store'), $data)
            ->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame('pending', $booking->status);
        $this->assertSame('Morgan Lee', $booking->customer->name);
        $this->assertSame('12 Jalan Test, Kampar', $booking->pickup_address);
        $this->assertSame('Unit 4, Example Apartments, Kampar', $booking->dropoff_address);
        $this->get(route('bookings.confirmation', $booking->reference))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Computer/PC Troubleshooting')
            ->assertSee('12 Jalan Test, Kampar')
            ->assertSee('Unit 4, Example Apartments, Kampar')
            ->assertSee('guestNavbar', false)
            ->assertSee('Back to home');

        $this->from(route('bookings.create', ['date' => $dateString]))
            ->post(route('bookings.store'), $data)
            ->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('bookings', 1);

        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $this->actingAs($staff)->put(route('bookings.update', $booking), [
            'status' => 'confirmed',
            'assigned_to' => $staff->id,
        ])->assertSessionHas('success', 'Booking updated.');
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame($staff->id, $booking->fresh()->assigned_to);
        $ticket = $booking->fresh()->repairTicket;
        $this->assertNotNull($ticket);
        $this->assertSame($ticket->id, $booking->fresh()->repair_ticket_id);
        $this->assertSame($booking->customer_id, $ticket->customer_id);
        $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Pickup and drop-off')
            ->assertSee('12 Jalan Test, Kampar')
            ->assertSee('Unit 4, Example Apartments, Kampar');
        $this->get(route('bookings.show', $booking->fresh()))
            ->assertOk()
            ->assertSee('Pickup address')
            ->assertSee('Drop-off address')
            ->assertSee('12 Jalan Test, Kampar')
            ->assertSee('Unit 4, Example Apartments, Kampar');
        $this->assertSame($staff->id, $ticket->assigned_to);
        $this->assertSame(1, $ticket->updates()->count());

        $this->actingAs($staff)->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('All bookings')
            ->assertSee('href="#bookingsMenu"', false)
            ->assertSee('id="bookingsMenu"', false)
            ->assertSee('<li class="breadcrumb-item active" aria-current="page">Bookings</li>', false)
            ->assertDontSee('<li class="breadcrumb-item">Work</li>', false)
            ->assertDontSee('<li class="breadcrumb-item">Bookings</li>', false);
        $this->assertSame('/bookings/calendar', parse_url(route('bookings.calendar'), PHP_URL_PATH));
        $this->actingAs($staff)->get(route('bookings.calendar'))
            ->assertOk()
            ->assertSee('id="bookings-calendar"', false)
            ->assertSee('id="booking-details-modal"', false)
            ->assertSee('eventClick: (info)', false)
            ->assertSee('<li class="breadcrumb-item active" aria-current="page">Book Calendar</li>', false)
            ->assertDontSee('<li class="breadcrumb-item">Work</li>', false);
        $this->actingAs($staff)->getJson(route('bookings.calendar.events', [
            'start' => $booking->starts_at->copy()->subDay()->setTimezone('UTC')->toIso8601String(),
            'end' => $booking->starts_at->copy()->addDay()->setTimezone('UTC')->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.extendedProps.reference', $booking->reference)
            ->assertJsonPath('0.backgroundColor', Booking::CALENDAR_COLORS['confirmed']['background'])
            ->assertJsonPath('0.textColor', Booking::CALENDAR_COLORS['confirmed']['text'])
            ->assertJsonPath('0.extendedProps.booking_url', route('bookings.show', $booking))
            ->assertJsonPath('0.extendedProps.customer_url', route('customers.show', $booking->customer));
        $this->actingAs($staff)->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Morgan Lee');
        $this->actingAs($staff)->getJson(route('bookings.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Morgan Lee'],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.customer_contact', fn (string $contact): bool => str_contains(
                $contact,
                'href="'.route('customers.show', $booking->customer).'"',
            ))
            ->assertJsonPath('data.0.status_label', '<span class="badge text-bg-success">Confirmed</span>')
            ->assertJsonPath('data.0.management', fn (string $management): bool => str_contains($management, 'js-user-search')
                && str_contains($management, 'Search staff by name'))
            ->assertSee('Morgan Lee')
            ->assertSee($booking->reference);

        $this->actingAs($staff)->put(route('bookings.update', $booking), [
            'status' => 'confirmed',
            'assigned_to' => $staff->id,
        ])->assertSessionHas('success', 'Booking updated.');
        $this->assertSame(1, RepairTicket::query()->count());
    }

    public function test_customer_booking_requires_a_valid_email_address(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        $data = [
            'customer_name' => 'Booking Customer',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'device_model' => 'Dell XPS 13',
            'request_notes' => 'The fan is noisy.',
            'booking_date' => $date->toDateString(),
            'start_time' => '11:00',
        ];

        foreach (['', 'not-an-email'] as $email) {
            $this->from(route('bookings.create', ['date' => $date->toDateString()]))
                ->post(route('bookings.store'), [...$data, 'customer_email' => $email])
                ->assertSessionHasErrors('customer_email');
        }

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_pickup_and_dropoff_pins_outside_kampar_are_rejected(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        foreach ([
            ['pickup', '4.5', '101.15'],
            ['dropoff', '-4.326', '-78.85'],
        ] as [$location, $latitude, $longitude]) {
            $this->post(route('bookings.store'), [
                'customer_name' => 'Booking Customer',
                'customer_email' => 'booking-customer@example.test',
                'customer_phone' => '+60112223333',
                'service_id' => 'basic-troubleshooting',
                'booking_date' => $date->toDateString(),
                'start_time' => '11:00',
                $location.'_latitude' => $latitude,
                $location.'_longitude' => $longitude,
            ])->assertSessionHasErrors($location.'_latitude');
        }

        $this->post(route('bookings.store'), [
            'customer_name' => 'Booking Customer',
            'customer_email' => 'booking-customer@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'pickup_address' => 'Outside Kampar',
            'booking_date' => $date->toDateString(),
            'start_time' => '11:00',
        ])->assertSessionHasErrors('pickup_address');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_fyp_booking_does_not_store_computer_troubleshooting_details(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        $bookingPage = $this->get(route('bookings.create'))
            ->assertOk()
            ->assertSee('booking-location-map')
            ->assertSee('pickup-location-card', false)
            ->assertSee('dropoff-location-card', false)
            ->assertSee('pickup-location-preview', false)
            ->assertSee('dropoff-location-preview', false)
            ->assertSee('booking-location-active-mode', false)
            ->assertSee('booking-location-modal', false)
            ->assertSee('locationPickerModal.show()', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('dropoff-same-as-pickup', false)
            ->assertSee('dropoffSameAsPickup.checked', false)
            ->assertSee('Choose a pin button to open the map and set that location.', false)
            ->assertSee('booking-out-of-area-modal', false)
            ->assertSee('maxBoundsViscosity: 1', false)
            ->assertSee('serviceAreaRadiusKm * 1000', false)
            ->assertSee('<svg viewBox="0 0 28 40"', false)
            ->assertDontSee('bi-geo-alt-fill')
            ->assertSee('Contact us on WhatsApp or email', false)
            ->assertSee('data-booking-location-mode="pickup"', false)
            ->assertSee('data-booking-location-mode="dropoff"', false);
        $document = new \DOMDocument();
        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);
        $document->loadHTML($bookingPage->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlErrorSetting);
        $xpath = new \DOMXPath($document);
        $this->assertSame(
            0,
            $xpath->query('//*[@id="booking-out-of-area-modal"]/ancestor::*[@id="booking-times-modal"]')->length,
        );
        $this->assertSame(
            0,
            $xpath->query('//*[@id="booking-location-modal"]/ancestor::*[@id="booking-times-modal"]')->length,
        );

        $this->post(route('bookings.store'), [
            'customer_name' => 'FYP Customer',
            'customer_email' => 'fyp@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'fyp-consultation',
            'device_model' => 'This should be excluded',
            'request_notes' => 'These details are only for PC troubleshooting.',
            'pickup_address' => 'A stale repair address',
            'pickup_latitude' => '999',
            'pickup_longitude' => '999',
            'booking_date' => $date->toDateString(),
            'start_time' => '11:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame('FYP Consultation', $booking->service_name);
        $this->assertNull($booking->device_model);
        $this->assertNull($booking->request_notes);
        $this->assertNull($booking->pickup_address);
        $this->assertNull($booking->pickup_latitude);
        $this->assertNull($booking->pickup_longitude);
    }

    public function test_computer_troubleshooting_booking_saves_device_details(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        $this->post(route('bookings.store'), [
            'customer_name' => 'PC Customer',
            'customer_email' => 'pc@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'device_model' => 'Dell XPS 13',
            'request_notes' => 'The fan is noisy.',
            'booking_date' => $date->toDateString(),
            'start_time' => '11:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame('Computer/PC Troubleshooting', $booking->service_name);
        $this->assertSame('Dell XPS 13', $booking->device_model);
        $this->assertSame('The fan is noisy.', $booking->request_notes);
    }

    public function test_dropoff_can_reuse_the_pickup_address_and_pin(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        $this->post(route('bookings.store'), [
            'customer_name' => 'Same Location Customer',
            'customer_email' => 'same-location@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'pickup_address' => '12 Jalan Test, Kampar',
            'pickup_latitude' => '4.326',
            'pickup_longitude' => '101.15',
            'dropoff_same_as_pickup' => '1',
            'booking_date' => $date->toDateString(),
            'start_time' => '11:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame($booking->pickup_address, $booking->dropoff_address);
        $this->assertSame($booking->pickup_latitude, $booking->dropoff_latitude);
        $this->assertSame($booking->pickup_longitude, $booking->dropoff_longitude);
    }

    public function test_staff_can_create_a_repair_ticket_from_a_booking(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Booking Customer',
            'email' => 'booking-customer@example.test',
            'phone' => '+60112223333',
        ]);
        $booking = Booking::query()->create([
            'reference' => 'BK-TICKET-TEST',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'service_id' => 'laptop-cleaning',
            'service_name' => 'Laptop cleaning',
            'device_model' => 'Dell XPS 13',
            'request_notes' => 'Fan is noisy.',
            'starts_at' => Carbon::now('UTC')->addDays(2),
            'duration_minutes' => 60,
            'status' => 'pending',
        ]);

        $this->actingAs($staff)->get(route('bookings.show', $booking))
            ->assertOk()
            ->assertSee('Create repair ticket')
            ->assertSee(route('tickets.create', ['booking_id' => $booking->id]), false);
        $this->get(route('tickets.create', ['booking_id' => $booking->id]))
            ->assertOk()
            ->assertSee('Create ticket from booking')
            ->assertSee('Laptop cleaning')
            ->assertSee('Fan is noisy.')
            ->assertSee('Dell XPS 13');

        $ticketData = [
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'device_type' => 'Computer',
            'device_model' => 'Dell XPS 13',
            'issue_description' => "Laptop cleaning\n\nFan is noisy.",
            'priority' => 'normal',
        ];
        $this->post(route('tickets.store'), $ticketData)->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertSame($ticket->id, $booking->fresh()->repair_ticket_id);
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame('Dell XPS 13', $ticket->device_model);

        $this->get(route('bookings.show', $booking->fresh()))
            ->assertOk()
            ->assertSee('Open repair ticket')
            ->assertSee(route('tickets.show', $ticket), false)
            ->assertDontSee('Create repair ticket');
        $this->from(route('tickets.create', ['booking_id' => $booking->id]))
            ->post(route('tickets.store'), $ticketData)
            ->assertSessionHasErrors('booking_id');
        $this->assertSame(1, RepairTicket::query()->count());
    }

    public function test_repair_booking_addresses_are_saved_and_displayed_on_its_ticket(): void
    {
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        $this->post(route('bookings.store'), [
            'customer_name' => 'Pickup Customer',
            'customer_email' => 'pickup-customer@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'basic-troubleshooting',
            'device_model' => 'Dell XPS 13',
            'pickup_address' => '12 Jalan Test, Kampar',
            'dropoff_address' => "Unit 4, Example Apartments\nKampar",
            'pickup_latitude' => '4.3260000',
            'pickup_longitude' => '101.1500000',
            'dropoff_latitude' => '4.3270000',
            'dropoff_longitude' => '101.1510000',
            'request_notes' => 'Screen flickers.',
            'booking_date' => $date->toDateString(),
            'start_time' => '11:00',
        ])->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame('12 Jalan Test, Kampar', $booking->pickup_address);
        $this->assertSame("Unit 4, Example Apartments\nKampar", $booking->dropoff_address);
        $this->assertEqualsWithDelta(4.326, (float) $booking->pickup_latitude, 0.0000001);
        $this->assertEqualsWithDelta(101.15, (float) $booking->pickup_longitude, 0.0000001);
        $this->get(route('bookings.confirmation', $booking->reference))
            ->assertOk()
            ->assertSee('12 Jalan Test, Kampar')
            ->assertSee('Unit 4, Example Apartments')
            ->assertSee('View pickup pin')
            ->assertSee('View drop-off pin');

        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $this->actingAs($staff)->post(route('tickets.store'), [
            'booking_id' => $booking->id,
            'device_type' => 'Computer',
            'device_model' => 'Dell XPS 13',
            'issue_description' => "Computer/PC Troubleshooting\n\nScreen flickers.",
            'priority' => 'normal',
        ])->assertSessionHasNoErrors();

        $ticket = $booking->fresh()->repairTicket;
        $this->assertNotNull($ticket);
        $this->assertSame($booking->pickup_address, $ticket->pickup_address);
        $this->assertSame($booking->dropoff_address, $ticket->dropoff_address);
        $this->get(route('bookings.show', $booking->fresh()))
            ->assertOk()
            ->assertSee('Pickup address')
            ->assertSee('Drop-off address')
            ->assertSee('12 Jalan Test, Kampar')
            ->assertSee('Unit 4, Example Apartments')
            ->assertSee('View pickup pin')
            ->assertSee('View drop-off pin');
        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Pickup and drop-off')
            ->assertSee('12 Jalan Test, Kampar')
            ->assertSee('Unit 4, Example Apartments')
            ->assertSee('View pickup pin')
            ->assertSee('View drop-off pin');
    }

    public function test_booking_datatable_filters_by_service_customer_and_appointment_datetime(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(5)->setTime(11, 30);
        foreach ([
            ['reference' => 'BK-FILTER-1', 'customer_name' => 'Avery Tan', 'service_name' => 'Laptop cleaning', 'starts_at' => $date->copy()->setTimezone('UTC')],
            ['reference' => 'BK-FILTER-2', 'customer_name' => 'Avery Tan', 'service_name' => 'Laptop repair', 'starts_at' => $date->copy()->addHour()->setTimezone('UTC')],
            ['reference' => 'BK-FILTER-3', 'customer_name' => 'Jordan Lee', 'service_name' => 'Laptop cleaning', 'starts_at' => $date->copy()->addHours(2)->setTimezone('UTC')],
        ] as $bookingData) {
            Booking::query()->create([
                ...$bookingData,
                'customer_email' => str($bookingData['customer_name'])->slug('.').'@example.test',
                'customer_phone' => '+60112223333',
                'service_id' => str($bookingData['service_name'])->slug('-'),
                'duration_minutes' => 60,
                'status' => 'pending',
            ]);
        }

        $this->actingAs($staff)->getJson(route('bookings.datatable', [
            'service' => 'Laptop cleaning',
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson(route('bookings.datatable', [
            'filter' => ['service' => 'Laptop cleaning'],
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertJsonPath('recordsFiltered', 2);
        $this->getJson(route('bookings.datatable', [
            'customer_name' => 'Avery',
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertJsonPath('recordsFiltered', 2);
        $this->getJson(route('bookings.datatable', [
            'starts_from' => $date->format('Y-m-d\TH:i'),
            'starts_to' => $date->format('Y-m-d\TH:i'),
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertJsonPath('recordsFiltered', 1);
        $datatableResponse = $this->getJson(route('bookings.datatable', [
            'filter' => [
                'service' => 'Laptop cleaning',
                'customer_name' => 'Avery',
                'starts_from' => $date->format('Y-m-d\TH:i'),
                'starts_to' => $date->format('Y-m-d\TH:i'),
            ],
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]));
        $datatableResponse->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.reference', 'BK-FILTER-1')
            ->assertJsonPath('data.0.starts_at', $date->format('M j, Y g:i A').' MYT');
        $management = $datatableResponse->json('data.0.management');
        $this->assertStringContainsString('data-booking-update', $management);
        $this->assertStringContainsString('data-bs-toggle="modal"', $management);
        $this->assertStringContainsString('data-bs-target="#booking-status-modal"', $management);

        $this->get(route('bookings.index', [
            'service' => 'Laptop cleaning',
            'customer_name' => 'Avery',
            'starts_from' => $date->format('Y-m-d\TH:i'),
            'starts_to' => $date->format('Y-m-d\TH:i'),
        ]))->assertOk()
            ->assertSee('id="booking-filters-modal"', false)
            ->assertSee('booking-filter-fab')
            ->assertSee('d-none d-lg-inline-flex')
            ->assertSee('data-bs-target="#booking-filters-modal"', false)
            ->assertSee('Filter bookings')
            ->assertSee('name="filter[service]"', false)
            ->assertSee('name="filter[customer_name]"', false)
            ->assertSee('name="filter[starts_from]"', false)
            ->assertSee('name="filter[starts_to]"', false)
            ->assertSee('Laptop cleaning');
    }

    public function test_landing_page_links_to_the_live_booking_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('bookings.create'))
            ->assertSee('Packages')
            ->assertDontSee('Website pricing')
            ->assertSee('https://wa.me/60104224350')
            ->assertSee('Message us on WhatsApp')
            ->assertSee('Choose the service you need')
            ->assertDontSee('Online service booking is coming soon.');
    }

    public function test_customer_calendar_only_returns_the_signed_in_customers_appointments(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create(['email' => 'customer@example.test']);
        $customer->assignRole('customer');
        $startsAt = Carbon::now('UTC')->addDays(7)->startOfHour();

        $createdBookings = collect();
        foreach ([
            ['reference' => 'BK-CUSTOMER-1', 'customer_email' => $customer->email, 'starts_at' => $startsAt],
            ['reference' => 'BK-OTHER-1', 'customer_email' => 'other@example.test', 'starts_at' => $startsAt->copy()->addHour()],
        ] as $booking) {
            $createdBookings->push(Booking::query()->create([
                ...$booking,
                'customer_name' => 'Calendar Customer',
                'customer_phone' => '+60112223333',
                'service_id' => 'laptop-cleaning',
                'service_name' => 'Laptop cleaning',
                'duration_minutes' => 60,
                'status' => 'confirmed',
            ]));
        }

        $this->actingAs($customer)->get(route('bookings.calendar'))
            ->assertOk()
            ->assertSee('Book Calendar')
            ->assertSee("timeZone: 'UTC'", false);
        $this->get(route('bookings.show', $createdBookings[1]))->assertNotFound();
        $this->get(route('bookings.show', $createdBookings[0]))
            ->assertOk()
            ->assertSee('BK-CUSTOMER-1');
        $localStart = $startsAt->copy()->setTimezone('Asia/Kuala_Lumpur');
        $this->getJson(route('bookings.calendar.events', [
            'start' => $localStart->copy()->startOfDay()->format('Y-m-d\T00:00:00\Z'),
            'end' => $localStart->copy()->addDay()->startOfDay()->format('Y-m-d\T00:00:00\Z'),
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.extendedProps.reference', 'BK-CUSTOMER-1')
            ->assertJsonPath('0.start', $localStart->format('Y-m-d\TH:i:s\Z'));
    }

    public function test_booking_submission_shows_a_confirmation_modal_with_details(): void
    {
        $this->get('/book')
            ->assertOk()
            ->assertSee('id="booking-confirmation-modal"', false)
            ->assertSee('id="booking-confirmation-details"', false)
            ->assertSee('id="booking-confirmation-map"', false)
            ->assertSee('id="booking-confirmation-location-section"', false)
            ->assertSee('Map preview')
            ->assertSee('Confirm your booking')
            ->assertSee('Edit details')
            ->assertSee('Confirm booking')
            ->assertSee('bookingConfirmationModal.show()', false)
            ->assertSee('confirmationMap.fitBounds', false)
            ->assertSee('bookingForm.requestSubmit()', false);
    }

    public function test_booking_calendar_is_basic_and_availability_comes_from_the_database(): void
    {
        $this->get('/book')
            ->assertOk()
            ->assertSee('id="calendar"', false)
            ->assertDontSee('window.location.href =')
            ->assertSee('new FullCalendar.Calendar(calendarEl, {', false)
            ->assertSee('timeZone: "UTC"', false)
            ->assertSee('initialView: "dayGridMonth"', false)
            ->assertSee('weekends: false', false)
            ->assertSee('events: availabilityEventsUrl', false)
            ->assertSee('eventsSet:', false)
            ->assertSee('dayCellClassNames:', false)
            ->assertSee('booking-date-available', false)
            ->assertSee('#dcfce7', false)
            ->assertSee('.fc-day-today', false)
            ->assertSee('#ffb51b', false)
            ->assertSee('#calendar .fc-day-today .fc-daygrid-day-number { color: #fff !important; background: #ffb51b', false)
            ->assertSee('#calendar .booking-date-selected.fc-day-today .fc-daygrid-day-number { color: #fff !important; background: #ffb51b !important', false)
            ->assertSee('booking-date-unavailable', false)
            ->assertSee('if (!availableAppointmentDates.has(info.dateStr))', false)
            ->assertSee('booking-date-selected', false)
            ->assertSee('info.dayEl.classList.add("booking-date-selected")', false)
            ->assertSee('calendar.select(info.dateStr)', false)
            ->assertSee('bookingDate.value = info.dateStr', false)
            ->assertSee('booking-times-modal', false)
            ->assertSee('modal-dialog-centered', false)
            ->assertSee('justify-content-center gap-3', false)
            ->assertSee('availabilityUrl + "?date="', false)
            ->assertSee('timesModal.show()', false)
            ->assertSee('button.className = "btn btn-outline-primary btn-lg"', false)
            ->assertSee('bookingTime.value = slot', false)
            ->assertSee('id="booking-date"', false)
            ->assertSee('id="booking-time"', false)
            ->assertSee('id="booking-date-summary"', false)
            ->assertSee('id="booking-time-summary"', false)
            ->assertSee('type="hidden" id="booking-date"', false)
            ->assertSee('type="hidden" id="booking-time"', false)
            ->assertSee('id="booking-change-time"', false)
            ->assertSee('type="hidden" id="booking-date" name="booking_date" value=""', false)
            ->assertDontSee('type="date" id="booking-date"', false)
            ->assertDontSee('type="time" id="booking-time"', false)
            ->assertDontSee('alert(info.dateStr)', false)
            ->assertSee('calendar-availability-status', false);

        $date = Carbon::now('Asia/Kuala_Lumpur')->next(Carbon::MONDAY);
        $this->getJson(route('bookings.availability', ['date' => $date->toDateString()]))
            ->assertOk()
            ->assertJsonPath('date', $date->toDateString())
            ->assertJsonPath('slots.0', '09:00')
            ->assertJsonPath('slots.8', '17:00');

        $this->getJson(route('bookings.events', [
            'start' => $date->format('Y-m-d\T00:00:00\Z'),
            'end' => $date->copy()->addWeek()->format('Y-m-d\T00:00:00\Z'),
        ]))->assertOk()
            ->assertJsonFragment(['start' => $date->toDateString(), 'available' => true])
            ->assertJsonPath('0.color', '#dcfce7')
            ->assertJsonMissing(['title' => 'Available'])
            ->assertJsonMissing(['start' => $date->copy()->next(Carbon::SUNDAY)->toDateString()]);
    }

    public function test_customer_can_book_from_the_authenticated_page_and_identity_comes_from_the_account(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create([
            'name' => 'Signed-in Customer',
            'email' => 'signed-in@example.test',
            'phone' => '+60112223333',
        ]);
        $customer->assignRole('customer');
        $date = Carbon::now('Asia/Kuala_Lumpur')->addDays(4);
        while ($date->isSunday()) {
            $date->addDay();
        }

        $this->get(route('bookings.account-create'))
            ->assertRedirect(route('login'));

        $this->actingAs($customer)
            ->get(route('bookings.account-create'))
            ->assertOk()
            ->assertSee('Book a service')
            ->assertSee('My bookings')
            ->assertDontSee('data-header-page-search', false)
            ->assertSee('data-notifications-user="'.$customer->id.'"', false)
            ->assertSee('value="Signed-in Customer"', false)
            ->assertSee('value="signed-in@example.test"', false)
            ->assertSee('readonly', false)
            ->assertDontSee('guestNavbar', false);

        $this->post(route('bookings.account-store'), [
            'customer_name' => 'Impersonated Name',
            'customer_email' => 'someone-else@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'fyp-consultation',
            'booking_date' => $date->toDateString(),
            'start_time' => '11:00',
        ])->assertRedirect();

        $booking = Booking::query()->firstOrFail();
        $this->assertSame('Signed-in Customer', $booking->customer_name);
        $this->assertSame('signed-in@example.test', $booking->customer_email);
    }

    public function test_staff_can_create_edit_and_delete_recurring_booking_times(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('member');
        $staff->givePermissionTo('booking_availability.manage');
        $this->actingAs($staff);
        $this->assertSame('/booking/slots', parse_url(route('booking-time-slots.index'), PHP_URL_PATH));
        $this->get(route('booking-time-slots.index'))
            ->assertOk()
            ->assertSee('Available booking times')
            ->assertSee('id="availability-calendar"', false)
            ->assertSee('id="slot-modal"', false)
            ->assertSee("fetch(url, {", false)
            ->assertSee("method: method,", false)
            ->assertSee("const details = event.extendedProps;", false)
            ->assertSee("initialView: 'timeGridWeek'", false)
            ->assertSee('dayGridMonth,timeGridWeek,timeGridDay,listWeek', false);
        $this->get(route('booking-time-slots.create'))->assertOk();

        $this->post(route('booking-time-slots.store'), [
            'day_of_week' => Carbon::SUNDAY,
            'start_time' => '11:30',
        ])->assertRedirect();
        $slot = BookingTimeSlot::query()->where('day_of_week', Carbon::SUNDAY)->firstOrFail();
        $this->get(route('booking-time-slots.index'))
            ->assertOk()
            ->assertSee('11:30 AM')
            ->assertViewHas('events', function (array $events) use ($slot): bool {
                return collect($events)->contains(fn (array $event): bool => $event['daysOfWeek'] === [Carbon::SUNDAY]
                    && $event['startTime'] === '11:30'
                    && $event['url'] === route('booking-time-slots.show', $slot)
                    && $event['backgroundColor'] === '#d9f8eb'
                    && $event['textColor'] === '#00875a');
            });
        $this->get(route('booking-time-slots.show', $slot))->assertOk()->assertSee('Sunday');
        $this->get(route('booking-time-slots.edit', $slot))->assertOk();
        $this->put(route('booking-time-slots.update', $slot), [
            'day_of_week' => Carbon::SUNDAY,
            'start_time' => '12:30',
        ])->assertRedirect(route('booking-time-slots.show', $slot));

        $sunday = Carbon::now('Asia/Kuala_Lumpur')->next(Carbon::SUNDAY);
        $this->getJson(route('bookings.availability', ['date' => $sunday->toDateString()]))
            ->assertOk()->assertJsonPath('slots.0', '12:30');

        $this->get(route('booking-time-slots.delete', $slot))->assertOk();
        $this->delete(route('booking-time-slots.destroy', $slot))->assertRedirect(route('booking-time-slots.index'));
        $this->assertDatabaseMissing('booking_time_slots', ['id' => $slot->id]);
        $this->getJson(route('bookings.availability', ['date' => $sunday->toDateString()]))
            ->assertOk()->assertJsonPath('slots', []);
    }

    public function test_booking_time_slot_crud_supports_ajax_requests(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('member');
        $staff->givePermissionTo('booking_availability.manage');
        $this->actingAs($staff);

        $this->postJson(route('booking-time-slots.store'), [
            'day_of_week' => Carbon::SUNDAY,
            'start_time' => '11:30',
        ])->assertCreated()
            ->assertJsonPath('message', 'Available time created.')
            ->assertJsonPath('event.extendedProps.dayOfWeek', (string) Carbon::SUNDAY);
        $slot = BookingTimeSlot::query()->where('day_of_week', Carbon::SUNDAY)->firstOrFail();

        $this->putJson(route('booking-time-slots.update', $slot), [
            'day_of_week' => Carbon::SUNDAY,
            'start_time' => '12:30',
        ])->assertOk()
            ->assertJsonPath('message', 'Available time updated.')
            ->assertJsonPath('event.extendedProps.startTime', '12:30');

        $this->deleteJson(route('booking-time-slots.destroy', $slot))
            ->assertOk()
            ->assertJsonPath('message', 'Available time deleted.')
            ->assertJsonPath('id', (string) $slot->id);
        $this->assertDatabaseMissing('booking_time_slots', ['id' => $slot->id]);
    }
}
