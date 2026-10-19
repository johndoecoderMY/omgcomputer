<?php

namespace App\Console\Commands;

use App\Models\MetaContentPost;
use App\Services\MetaContentPublisher;
use Illuminate\Console\Command;
use RuntimeException;

class PublishScheduledMetaContent extends Command
{
    protected $signature = 'meta-content:publish-scheduled';

    protected $description = 'Publish due Facebook and Instagram content.';

    public function handle(MetaContentPublisher $publisher): int
    {
        MetaContentPost::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->chunkById(50, function ($posts) use ($publisher): void {
                foreach ($posts as $post) {
                    try {
                        $metaId = $publisher->publish($post);
                        $post->update([
                            'status' => 'published',
                            'published_at' => now(),
                            'meta_post_id' => $metaId,
                            'error_message' => null,
                            'scheduled_at' => null,
                        ]);
                    } catch (RuntimeException $exception) {
                        $post->update([
                            'status' => 'failed',
                            'error_message' => $exception->getMessage(),
                            'scheduled_at' => null,
                        ]);
                        report($exception);
                    }
                }
            });

        return self::SUCCESS;
    }
}
