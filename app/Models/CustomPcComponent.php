<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomPcComponent extends Model
{
    protected $fillable = [
        'source_inventory_part_id',
        'sku',
        'name',
        'type',
        'description',
        'catalog_price',
        'catalog_currency',
        'cost_price',
        'selling_price',
    ];

    protected function casts(): array
    {
        return [
            'catalog_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
        ];
    }

    public function sourceInventoryPart(): BelongsTo
    {
        return $this->belongsTo(InventoryPart::class, 'source_inventory_part_id');
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class)
            ->withPivot('shopee_product_url', 'cost_price', 'unit_price')
            ->withTimestamps();
    }

    public function billingItems(): HasMany
    {
        return $this->hasMany(BillingDocumentItem::class);
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
