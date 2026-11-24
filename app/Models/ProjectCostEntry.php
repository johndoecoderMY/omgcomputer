<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCostEntry extends Model
{
    protected $fillable = ['project_id', 'category', 'title', 'amount', 'incurred_on'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'incurred_on' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
