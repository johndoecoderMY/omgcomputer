<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectFeature extends Model
{
    protected $fillable = [
        'name',
        'description',
        'priority',
        'status',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ProjectModule::class, 'project_module_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
