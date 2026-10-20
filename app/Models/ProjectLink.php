<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLink extends Model
{
    protected $fillable = [
        'project_id',
        'label',
        'url',
        'visible_to_customer',
    ];

    protected function casts(): array
    {
        return [
            'visible_to_customer' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
