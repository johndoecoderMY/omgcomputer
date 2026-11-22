<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    public const CALENDAR_COLORS = [
        'pending' => [
            'background' => '#ffe6d5',
            'text' => '#f5803e',
        ],
        'confirmed' => [
            'background' => '#e7f1ff',
            'text' => '#2c7be5',
        ],
        'completed' => [
            'background' => '#d9f8eb',
            'text' => '#00a884',
        ],
        'cancelled' => [
            'background' => '#fde2e2',
            'text' => '#b42318',
        ],
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
    ];

    public const EDITABLE_STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'reference',
        'customer_id',
        'repair_ticket_id',
        'assigned_to',
        'customer_name',
        'customer_email',
        'customer_phone',
        'service_id',
        'service_name',
        'device_model',
        'request_notes',
        'cancellation_reason',
        'customer_dropoff_at_shop',
        'pickup_address',
        'dropoff_address',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_latitude',
        'dropoff_longitude',
        'starts_at',
        'duration_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'customer_dropoff_at_shop' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function repairTicket(): BelongsTo
    {
        return $this->belongsTo(RepairTicket::class);
    }
}
