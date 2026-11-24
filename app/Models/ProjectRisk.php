<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectRisk extends Model
{
    protected $fillable = [
        'project_id', 'owner_id', 'title', 'description', 'probability',
        'impact', 'mitigation', 'status', 'due_on',
    ];

    protected function casts(): array
    {
        return ['due_on' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
