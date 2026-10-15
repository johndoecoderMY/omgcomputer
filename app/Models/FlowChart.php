<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowChart extends Model
{
    protected $fillable = [
        'title',
        'description',
        'steps',
    ];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
