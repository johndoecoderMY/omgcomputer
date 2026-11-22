<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SAMPLE_CAMPAIGN_NAME = 'Sample student laptop care campaign';

    public function up(): void
    {
        DB::table('marketing_campaigns')
            ->where('name', self::SAMPLE_CAMPAIGN_NAME)
            ->delete();
    }

    public function down(): void
    {
        if (DB::table('marketing_campaigns')->where('name', self::SAMPLE_CAMPAIGN_NAME)->exists()) {
            return;
        }

        DB::table('marketing_campaigns')->insert([
            'name' => self::SAMPLE_CAMPAIGN_NAME,
            'channel' => 'social',
            'status' => 'active',
            'starts_on' => now()->subDays(3)->toDateString(),
            'ends_on' => now()->addDays(14)->toDateString(),
            'spend' => 80,
            'impressions' => 1800,
            'clicks' => 95,
            'leads' => 12,
            'conversions' => 3,
            'attributed_revenue' => 360,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
