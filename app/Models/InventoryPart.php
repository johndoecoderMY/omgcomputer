<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryPart extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'cpu' => 'CPU',
        'gpu' => 'GPU',
        'ram' => 'RAM',
        'motherboard' => 'Motherboard',
        'storage' => 'Storage',
        'power_supply' => 'Power supply',
        'case' => 'Case',
        'cooling' => 'Cooling',
        'peripheral' => 'Peripheral',
        'other' => 'Other',
    ];

    protected $fillable = [
        'name',
        'sku',
        'type',
        'supplier_id',
        'description',
        'cost_price',
        'selling_price',
        'stock_quantity',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
