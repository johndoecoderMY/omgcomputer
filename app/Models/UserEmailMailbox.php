<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEmailMailbox extends Model
{
    protected $fillable = [
        'email',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
