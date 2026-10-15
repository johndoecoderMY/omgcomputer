<?php

namespace Tests\Feature;

use App\Models\Booking;
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
            ->assertSee('booking-calendar', false)
            ->assertSee('Book an appointment');

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
            ->assertSee('Laptop cleaning');

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

        $this->actingAs($staff)->put(route('bookings.update', $booking), [
            'status' => 'confirmed',
            'assigned_to' => $staff->id,
        ])->assertSessionHas('success', 'Booking updated.');
        $this->assertSame(1, RepairTicket::query()->count());
    }
}