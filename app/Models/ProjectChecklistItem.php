<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectChecklistItem extends Model
{
    protected $fillable = [
        'project_checklist_id', 'title', 'is_required', 'is_completed',
        'completed_by', 'completed_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'is_completed' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(ProjectChecklist::class, 'project_checklist_id');
    }
}
