<?php

namespace App\Http\Controllers;

use App\Models\BookingTimeSlot;
use App\Support\DataTableQueryBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class BookingTimeSlotController extends Controller
{
    public function index(): View
    {
        $events = BookingTimeSlot::query()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn (BookingTimeSlot $slot): array => [
                'title' => date('g:i A', strtotime($slot->start_time)),
                'daysOfWeek' => [(int) $slot->day_of_week],
                'startTime' => substr($slot->start_time, 0, 5),
                'url' => route('booking-time-slots.show', $slot),
                'backgroundColor' => '#d9f8eb',
                'borderColor' => '#b6efd9',
                'textColor' => '#00875a',
            ])
            ->all();

        return view('booking-time-slots.index', compact('events'));
    }

    public function datatable(Request $request)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(BookingTimeSlot::query(), $request, [
            'day_of_week' => AllowedFilter::exact('day_of_week'),
            'start_time' => AllowedFilter::partial('start_time'),
        ]))
            ->addColumn('day_label', fn (BookingTimeSlot $slot): string => BookingTimeSlot::DAYS[$slot->day_of_week])
            ->editColumn('start_time', fn (BookingTimeSlot $slot): string => date('g:i A', strtotime($slot->start_time)))
            ->addColumn('actions', fn (BookingTimeSlot $slot): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">View</a>',
                e(route('booking-time-slots.show', $slot)),
            ))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('booking-time-slots.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $slot = BookingTimeSlot::query()->create($this->validatedData($request));

        return redirect()->route('booking-time-slots.show', $slot)->with('success', 'Available time created.');
    }

    public function show(BookingTimeSlot $bookingTimeSlot): View
    {
        return view('booking-time-slots.show', [
            'slot' => $bookingTimeSlot,
            'dayLabel' => BookingTimeSlot::DAYS[$bookingTimeSlot->day_of_week],
        ]);
    }

    public function edit(BookingTimeSlot $bookingTimeSlot): View
    {
        return view('booking-time-slots.edit', [
            'slot' => $bookingTimeSlot,
            'days' => BookingTimeSlot::DAYS,
        ]);
    }

    public function update(Request $request, BookingTimeSlot $bookingTimeSlot): RedirectResponse
    {
        $bookingTimeSlot->update($this->validatedData($request, $bookingTimeSlot));

        return redirect()->route('booking-time-slots.show', $bookingTimeSlot)->with('success', 'Available time updated.');
    }

    public function delete(BookingTimeSlot $bookingTimeSlot): View
    {
        return view('booking-time-slots.delete', compact('bookingTimeSlot'));
    }

    public function destroy(BookingTimeSlot $bookingTimeSlot): RedirectResponse
    {
        $bookingTimeSlot->delete();

        return redirect()->route('booking-time-slots.index')->with('success', 'Available time deleted.');
    }

    private function validatedData(Request $request, ?BookingTimeSlot $slot = null): array
    {
        return $request->validate([
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(BookingTimeSlot::DAYS))],
            'start_time' => [
                'required',
                'date_format:H:i',
                Rule::unique('booking_time_slots', 'start_time')
                    ->where('day_of_week', $request->input('day_of_week'))
                    ->ignore($slot?->id),
            ],
        ]);
    }
}
