<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningTaskComment extends Model
{
    protected $fillable = [
        'user_id',
        'body',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(PlanningTask::class, 'planning_task_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
