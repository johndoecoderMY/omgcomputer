<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;

class Task extends Model
{
    public const STATUSES = [
        'backlog' => 'To do',
        'in_progress' => 'In progress',
        'done' => 'Done',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    protected $fillable = [
        'project_id',
        'assigned_to',
        'flow_chart_id',
        'flow_chart_step_id',
        'parent_task_id',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
    ];

    protected static function booted(): void
    {
        static::created(function (self $task): void {
            if ($task->assigned_to) {
                $task->assignees()->syncWithoutDetaching([$task->assigned_to]);
            }
        });

        static::deleting(function (self $task): void {
            $taskIds = $task->subtasks()->pluck('id')->all();
            $task->assignees()->detach();
            if ($taskIds !== []) {
                DB::table('user_assignments')
                    ->where('assignable_type', self::class)
                    ->whereIn('assignable_id', $taskIds)
                    ->delete();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'start_date' => 'date',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('created_at');
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

    public function flowChart(): BelongsTo
    {
        return $this->belongsTo(FlowChart::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }
}
