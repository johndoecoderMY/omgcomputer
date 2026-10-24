<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    public const TYPES = [
        'consultation' => 'Consultation',
        'debugging' => 'Debugging session',
        'mentoring' => 'Mentoring',
        'setup_workshop' => 'Project setup workshop',
        'progress_review' => 'Progress review',
        'tech_clinic' => 'Small-group tech clinic',
        'other' => 'Other',
    ];

    public const COLORS = [
        'consultation' => '#2c7be5',
        'debugging' => '#e63757',
        'mentoring' => '#00a884',
        'setup_workshop' => '#6f42c1',
        'progress_review' => '#f5803e',
        'tech_clinic' => '#27bcfd',
        'other' => '#748194',
    ];

    public const BACKGROUND_COLORS = [
        'consultation' => '#e7f1ff',
        'debugging' => '#fbdbe1',
        'mentoring' => '#d9f8eb',
        'setup_workshop' => '#eee7f7',
        'progress_review' => '#ffe6d5',
        'tech_clinic' => '#d8f3ff',
        'other' => '#e6e8ec',
    ];

    protected $fillable = [
        'project_id',
        'title',
        'event_type',
        'starts_at',
        'ends_at',
        'location',
        'description',
        'visible_to_customer',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'visible_to_customer' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
