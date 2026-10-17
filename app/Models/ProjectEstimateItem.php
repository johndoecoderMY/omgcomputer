<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEstimateItem extends Model
{
    protected $fillable = [
        'category',
        'description',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getLineTotalAttribute(): float
    {
        return round($this->quantity * (float) $this->unit_price, 2);
    }
}
