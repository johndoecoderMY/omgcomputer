<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduledTasksTest extends TestCase
{
    public function test_scheduled_backup_runs_database_only(): void
    {
        $backupEvent = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'backup:run'));

        $this->assertNotNull($backupEvent);
        $this->assertStringContainsString('--only-db', $backupEvent->command);
        $this->assertStringNotContainsString('--only-files', $backupEvent->command);
    }
}
