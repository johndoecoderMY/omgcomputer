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

    protected $fillable = [
        'project_id',
        'title',
        'event_type',
        'starts_at',
        'ends_at',
        'location',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
