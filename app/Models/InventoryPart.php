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
        'operating_system' => 'Operating system',
        'software' => 'Software / Microsoft',
        'peripheral' => 'Peripheral',
        'ready_built_pc' => 'Ready-built PC',
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
        'is_for_sale',
        'stock_quantity',
        'reorder_level',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_for_sale' => 'boolean',
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

    public function specifications(): array
    {
        $specifications = [];
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->description) ?: [];

        foreach ($lines as $line) {
            $line = trim(preg_replace('/^[\s]*[-*•]\s*/u', '', $line) ?? '');
            if (preg_match('/^([^:]{1,60}):\s*(.+)$/u', $line, $matches)) {
                $specifications[trim($matches[1])] = trim($matches[2]);
            }
        }

        return $specifications;
    }

    public function specification(string $name): ?string
    {
        foreach ($this->specifications() as $label => $value) {
            if (strcasecmp($label, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
