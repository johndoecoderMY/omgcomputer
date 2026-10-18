<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingTimeSlot;
use App\Models\RepairTicket;
use App\Models\User;
use Carbon\Carbon;
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

        $staff = User::factory()->create();
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
            ->assertSee('data-datatable', false);
        $this->actingAs($staff)->getJson(route('bookings.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Morgan Lee'],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee('Morgan Lee')
            ->assertSee($booking->reference);

        $this->actingAs($staff)->put(route('bookings.update', $booking), [
            'status' => 'confirmed',
            'assigned_to' => $staff->id,
        ])->assertSessionHas('success', 'Booking updated.');
        $this->assertSame(1, RepairTicket::query()->count());
    }

    public function test_landing_page_links_to_the_live_booking_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('bookings.create'))
            ->assertSee('Choose the service you need')
            ->assertDontSee('Online service booking is coming soon.');
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
            ->assertDontSee('alert(info.dateStr)', false)
            ->assertDontSee('events:', false)
            ->assertDontSee('booking-times-modal', false);

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

    public function test_staff_can_create_edit_and_delete_recurring_booking_times(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('booking-time-slots.index'))->assertOk()->assertSee('Available booking times');
        $this->get(route('booking-time-slots.create'))->assertOk();

        $this->post(route('booking-time-slots.store'), [
            'day_of_week' => Carbon::SUNDAY,
            'start_time' => '11:30',
        ])->assertRedirect();
        $slot = BookingTimeSlot::query()->where('day_of_week', Carbon::SUNDAY)->firstOrFail();
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
