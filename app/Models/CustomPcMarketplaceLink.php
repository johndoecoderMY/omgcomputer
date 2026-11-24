<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomPcMarketplaceLink extends Model
{
    protected $table = 'custom_pc_component_marketplace_links';

    protected $fillable = [
        'custom_pc_component_id',
        'marketplace',
        'url',
        'category',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(CustomPcComponent::class, 'custom_pc_component_id');
    }
}
