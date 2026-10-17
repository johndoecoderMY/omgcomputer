<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowChart extends Model
{
    protected $fillable = [
        'title',
        'category',
        'subject_name',
        'is_example',
        'description',
        'steps',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'is_example' => 'boolean',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }
}
