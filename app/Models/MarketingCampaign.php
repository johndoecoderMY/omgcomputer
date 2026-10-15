<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    protected $fillable = [
        'name', 'channel', 'status', 'starts_on', 'ends_on', 'spend', 'impressions',
        'clicks', 'leads', 'conversions', 'attributed_revenue',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'spend' => 'decimal:2',
            'attributed_revenue' => 'decimal:2',
        ];
    }
}