<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectModule extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sort_order',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProjectFeature::class)->orderBy('name');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ProjectRequirement::class);
    }
}
