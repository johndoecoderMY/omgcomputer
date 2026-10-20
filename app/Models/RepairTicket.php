<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class RepairTicket extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'diagnosing' => 'Diagnosing',
        'waiting_customer' => 'Awaiting customer',
        'waiting_parts' => 'Waiting for parts',
        'in_repair' => 'In repair',
        'ready_for_pickup' => 'Ready for pickup',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'normal' => 'Normal',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'project_id',
        'assigned_to',
        'created_by_user_id',
        'device_type',
        'device_model',
        'serial_number',
        'issue_description',
        'diagnosis',
        'status',
        'priority',
        'due_at',
        'received_at',
    ];

    protected static function booted(): void
    {
        static::created(function (self $ticket): void {
            if ($ticket->assigned_to) {
                $ticket->assignees()->syncWithoutDetaching([$ticket->assigned_to]);
            }
        });

        static::deleting(function (self $ticket): void {
            $ticket->assignees()->detach();
        });
    }

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignees(): MorphToMany
    {
        return $this->morphToMany(User::class, 'assignable', 'user_assignments')->withTimestamps();
    }

    public function billingDocuments(): HasMany
    {
        return $this->hasMany(BillingDocument::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(RepairTicketUpdate::class)->latest();
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'repair_ticket_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }
}
