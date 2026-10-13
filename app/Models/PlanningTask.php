<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanningTask extends Model
{
    public const STATUSES = [
        'backlog' => 'Backlog',
        'planned' => 'Planned',
        'in_progress' => 'In progress',
        'blocked' => 'Blocked',
        'done' => 'Done',
    ];

    public const CATEGORIES = [
        'feature' => 'Website feature / improvement',
        'operations' => 'Shop operations',
        'task' => 'General task',
        'milestone' => 'Milestone',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    public const VISIBILITIES = [
        'personal' => 'Personal',
        'team' => 'Shared with team',
    ];

    protected $fillable = [
        'user_id',
        'project_id',
        'parent_task_id',
        'assigned_to',
        'title',
        'description',
        'category',
        'status',
        'priority',
        'visibility',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $task): void {
            if ($task->project_id === null && $task->user_id !== null) {
                $owner = User::query()->findOrFail($task->user_id);
                $task->project_id = PlanningProject::omgComputer($owner)->id;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PlanningProject::class, 'project_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PlanningTaskComment::class);
    }

    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('created_at');
    }
}
