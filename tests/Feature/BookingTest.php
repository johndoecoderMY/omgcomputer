<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\Customer;
use App\Models\RepairTicket;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

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
            ->assertSee('OMG Computer');

        $data = [
            'customer_name' => 'Morgan Lee',
            'customer_email' => 'morgan@example.test',
            'customer_phone' => '+60112223333',
            'service_id' => 'laptop-cleaning',
            'device_model' => 'Dell XPS 13',
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
        $this->get(route('bookings.confirmation', $booking->reference))
            ->assertOk()
            ->assertSee($booking->reference)
            ->assertSee('Laptop cleaning')
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
        $this->getJson(route('bookings.datatable', [
            'filter' => [
                'service' => 'Laptop cleaning',
                'customer_name' => 'Avery',
                'starts_from' => $date->format('Y-m-d\TH:i'),
                'starts_to' => $date->format('Y-m-d\TH:i'),
            ],
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.reference', 'BK-FILTER-1');

        $this->get(route('bookings.index', [
            'service' => 'Laptop cleaning',
            'customer_name' => 'Avery',
            'starts_from' => $date->format('Y-m-d\TH:i'),
            'starts_to' => $date->format('Y-m-d\TH:i'),
        ]))->assertOk()
            ->assertSee('id="booking-filters-modal"', false)
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
            ->assertSee('Book Calendar');
        $this->get(route('bookings.show', $createdBookings[1]))->assertNotFound();
        $this->get(route('bookings.show', $createdBookings[0]))
            ->assertOk()
            ->assertSee('BK-CUSTOMER-1');
        $this->getJson(route('bookings.calendar.events', [
            'start' => $startsAt->copy()->subHour()->toIso8601String(),
            'end' => $startsAt->copy()->addHours(2)->toIso8601String(),
        ]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.extendedProps.reference', 'BK-CUSTOMER-1');
    }

    public function test_booking_calendar_is_basic_and_availability_comes_from_the_database(): void
    {
        $this->get('/book')
            ->assertOk()
            ->assertSee('id="calendar"', false)
            ->assertDontSee('window.location.href =')
            ->assertSee('new FullCalendar.Calendar(calendarEl, {', false)
            ->assertSee('initialView: "dayGridMonth"', false)
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
            ->assertSee('readonly required', false)
            ->assertDontSee('alert(info.dateStr)', false)
            ->assertDontSee('events:', false);

        $date = Carbon::now('Asia/Kuala_Lumpur')->next(Carbon::MONDAY);
        $this->getJson(route('bookings.availability', ['date' => $date->toDateString()]))
            ->assertOk()
            ->assertJsonPath('date', $date->toDateString())
            ->assertJsonPath('slots.0', '09:00')
            ->assertJsonPath('slots.8', '17:00');

        $this->getJson(route('bookings.events', [
            'start' => $date->toDateString(),
            'end' => $date->copy()->addWeek()->toDateString(),
        ]))->assertOk()
            ->assertJsonFragment(['start' => $date->toDateString(), 'available' => true]);
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
            'service_id' => 'laptop-cleaning',
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
}
