<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_part_id',
        'user_id',
        'type',
        'quantity_change',
        'quantity_after',
        'reference',
        'note',
    ];

    public function part(): BelongsTo
    {
        return $this->belongsTo(InventoryPart::class, 'inventory_part_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}