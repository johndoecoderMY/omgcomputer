<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getProgressAttribute(): int
    {
        $total = $this->tasks_count ?? $this->tasks()->count();
        $completed = $this->completed_tasks_count
            ?? $this->tasks()->where('status', 'done')->count();

        return $total === 0 ? 0 : (int) round($completed / $total * 100);
    }
}
