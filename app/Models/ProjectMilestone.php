<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectMilestone extends Model
{
    public const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In progress',
        'done' => 'Done',
        'blocked' => 'Blocked',
    ];

    protected $fillable = [
        'depends_on_milestone_id',
        'name',
        'description',
        'deliverable',
        'starts_on',
        'due_on',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'due_on' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function dependency(): BelongsTo
    {
        return $this->belongsTo(self::class, 'depends_on_milestone_id');
    }

    public function dependentMilestones(): HasMany
    {
        return $this->hasMany(self::class, 'depends_on_milestone_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
