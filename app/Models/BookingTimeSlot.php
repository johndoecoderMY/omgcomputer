<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingTimeSlot extends Model
{
    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    protected $fillable = [
        'day_of_week',
        'start_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }
}
