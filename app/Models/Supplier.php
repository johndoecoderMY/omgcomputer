<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'contact_name',
        'email',
        'phone',
        'website',
        'notes',
        'shopee_shop_url',
        'shopee_shop_url_hash',
    ];

    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(InventoryPart::class)
            ->withPivot('shopee_product_url', 'cost_price', 'unit_price')
            ->withTimestamps();
    }

    public function customPcComponents(): BelongsToMany
    {
        return $this->belongsToMany(CustomPcComponent::class)
            ->withPivot('shopee_product_url', 'cost_price', 'unit_price')
            ->withTimestamps();
    }
}
