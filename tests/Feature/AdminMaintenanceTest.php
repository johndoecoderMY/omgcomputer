<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GitPullService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_access_system_maintenance(): void
    {
        Role::findOrCreate('member', 'web');
        Role::findOrCreate('admin', 'web');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get(route('admin.maintenance.index'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.maintenance.migrate'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.maintenance.migrate.fresh-seed'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.maintenance.optimize-clear'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.maintenance.build-assets'))->assertForbidden();
        $this->actingAs($member)->post(route('admin.maintenance.git-pull'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.maintenance.index'))
            ->assertOk()
            ->assertSee('System maintenance')
            ->assertSee('Build assets')
            ->assertSee('Run pending migrations')
            ->assertSee('Fresh migrate & seed')
            ->assertSee('Optimize clear')
            ->assertSee('Git pull');
    }

    public function test_administrator_can_pull_configured_git_branch(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->mock(GitPullService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('pull')->once();
        });

        $this->actingAs($admin)->post(route('admin.maintenance.git-pull'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Git pull completed. Review the site, then run migrations or clear caches if needed.');
    }

    public function test_git_pull_service_fast_forwards_a_clean_checkout(): void
    {
        Process::fake([
            '*' => Process::sequence([
                Process::result('true'),
                Process::result(),
                Process::result('Already up to date.'),
            ]),
        ]);

        app(GitPullService::class)->pull();

        Process::assertRan(fn ($process): bool => $process->command === ['git', 'rev-parse', '--is-inside-work-tree']);
        Process::assertRan(fn ($process): bool => $process->command === ['git', 'status', '--porcelain', '--untracked-files=no']);
        Process::assertRan(fn ($process): bool => $process->command === ['git', 'pull', '--ff-only']);
    }

    public function test_git_pull_service_refuses_to_update_a_dirty_checkout(): void
    {
        Process::fake([
            '*' => Process::sequence([
                Process::result('true'),
                Process::result(" M app/Example.php\n"),
            ]),
        ]);

        try {
            app(GitPullService::class)->pull();
            $this->fail('Expected a dirty Git working tree to stop the pull.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'Git pull stopped because tracked files have local changes. Commit or revert them before updating.',
                $exception->getMessage(),
            );
        }

        Process::assertDidntRun('git pull --ff-only');
    }

    public function test_administrator_can_run_migrations_and_clear_optimization_caches(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Nothing to migrate.');

        $this->actingAs($admin)->post(route('admin.maintenance.migrate'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Database migrations completed.')
            ->assertSessionHas('maintenance_output', 'Nothing to migrate.');

        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear', [])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Cached events cleared.');

        $this->actingAs($admin)->post(route('admin.maintenance.optimize-clear'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Laravel optimization caches cleared.')
            ->assertSessionHas('maintenance_output', 'Cached events cleared.');
    }

    public function test_administrator_can_fresh_migrate_and_seed_after_typed_confirmation(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate:fresh', ['--force' => true, '--seed' => true])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Migration table created. Seeders completed.');

        $this->actingAs($admin)->post(route('admin.maintenance.migrate.fresh-seed'), [
            'confirmation' => 'RESET AND SEED DATABASE',
        ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Database tables were recreated and seeders completed.')
            ->assertSessionHas('maintenance_output', 'Migration table created. Seeders completed.');
    }

    public function test_fresh_migration_requires_explicit_confirmation(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Artisan::shouldReceive('call')->never();

        $this->actingAs($admin)->from(route('admin.maintenance.index'))
            ->post(route('admin.maintenance.migrate.fresh-seed'), [
                'confirmation' => 'yes',
            ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHasErrors('confirmation');
    }

    public function test_administrator_can_build_frontend_assets(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Process::fake([
            '*' => Process::result('Vite build complete.'),
        ]);

        $this->actingAs($admin)->post(route('admin.maintenance.build-assets'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Frontend assets built successfully.')
            ->assertSessionHas('maintenance_output', 'Vite build complete.');

        Process::assertRan(fn ($process): bool => $process->command === ['npm', 'run', 'build']);
    }

    public function test_failed_frontend_build_shows_build_output(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Process::fake([
            '*' => Process::result('', 'npm is not installed.', 127),
        ]);

        $this->actingAs($admin)->post(route('admin.maintenance.build-assets'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_error')
            ->assertSessionHas('maintenance_output', 'npm is not installed.');
    }
}
