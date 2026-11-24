<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTimeEntry extends Model
{
    protected $fillable = ['project_id', 'user_id', 'worked_on', 'hours', 'hourly_rate', 'billable', 'notes'];

    protected function casts(): array
    {
        return ['worked_on' => 'date', 'hours' => 'decimal:2', 'hourly_rate' => 'decimal:2', 'billable' => 'boolean'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
