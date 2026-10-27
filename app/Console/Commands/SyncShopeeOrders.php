<?php

namespace App\Console\Commands;

use App\Models\MarketplaceShop;
use App\Services\Marketplace\MarketplaceApi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncShopeeOrders extends Command
{
    protected $signature = 'marketplaces:sync-shopee-orders';

    protected $description = 'Synchronize recent order changes from connected Shopee shops.';

    public function handle(MarketplaceApi $api): int
    {
        $failed = false;

        MarketplaceShop::query()
            ->where('platform', 'shopee')
            ->whereNotNull('access_token')
            ->orderBy('id')
            ->chunkById(50, function ($shops) use ($api, &$failed): void {
                foreach ($shops as $shop) {
                    try {
                        $count = $api->syncRecentShopeeOrders($shop);
                        $this->info("Synchronized {$count} order records for {$shop->shop_name}.");
                    } catch (RuntimeException $exception) {
                        $failed = true;
                        Log::warning('Scheduled Shopee order sync failed.', [
                            'shop_id' => $shop->id,
                            'exception' => $exception::class,
                            'message' => $exception->getMessage(),
                        ]);
                        $this->error("Could not sync {$shop->shop_name}: {$exception->getMessage()}");
                    }
                }
            });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
