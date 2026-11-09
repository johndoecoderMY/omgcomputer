<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectQualityItem extends Model
{
    public const KINDS = [
        'test_case' => 'Test case',
        'issue' => 'Issue',
    ];

    protected $fillable = [
        'project_requirement_id',
        'kind',
        'title',
        'description',
        'expected_result',
        'actual_result',
        'priority',
        'severity',
        'status',
        'resolution',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ProjectRequirement::class, 'project_requirement_id');
    }
}
