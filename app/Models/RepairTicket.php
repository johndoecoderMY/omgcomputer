<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairTicket extends Model
{
    public const STATUSES = [
        'received' => 'Received',
        'in_progress' => 'In progress',
        'waiting_customer' => 'Waiting for customer',
        'waiting_parts' => 'Waiting for parts',
        'ready_for_pickup' => 'Ready for pickup',
        'closed' => 'Closed',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'created_by',
        'assigned_to',
        'ticket_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'device_type',
        'device_model',
        'serial_number',
        'subject',
        'issue_description',
        'diagnostic_notes',
        'status',
        'priority',
        'quoted_amount',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'quoted_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
