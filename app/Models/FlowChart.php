<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
