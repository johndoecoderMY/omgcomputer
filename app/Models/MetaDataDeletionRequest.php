<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaDataDeletionRequest extends Model
{
    protected $fillable = [
        'confirmation_code_hash',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }
}
