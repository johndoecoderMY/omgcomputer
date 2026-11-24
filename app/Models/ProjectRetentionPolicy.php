<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRetentionPolicy extends Model
{
    protected $fillable = ['project_id', 'retention_days', 'action', 'legal_hold', 'archived_at'];

    protected function casts(): array
    {
        return ['retention_days' => 'integer', 'legal_hold' => 'boolean', 'archived_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
