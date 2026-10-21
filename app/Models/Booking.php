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
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
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
        'starts_at',
        'duration_minutes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
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
