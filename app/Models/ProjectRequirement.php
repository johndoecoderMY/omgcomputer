<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectRequirement extends Model
{
    public const TYPES = [
        'business' => 'Business',
        'functional' => 'Functional',
        'non_functional' => 'Non-functional',
    ];

    public const PRIORITIES = [
        'must' => 'Must have',
        'should' => 'Should have',
        'could' => 'Could have',
        'wont' => "Won't have",
    ];

    public const STATUSES = [
        'planned' => 'Planned',
        'approved' => 'Approved',
        'in_progress' => 'In progress',
        'done' => 'Done',
        'deferred' => 'Deferred',
    ];

    protected $fillable = [
        'code',
        'requirement',
        'requirement_type',
        'priority',
        'status',
        'user_story',
        'acceptance_criteria',
        'project_module_id',
        'parent_requirement_id',
        'is_completed',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(ProjectModule::class, 'project_module_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_requirement_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_requirement_id')->orderBy('sort_order');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
