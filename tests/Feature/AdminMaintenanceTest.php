<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\RepairTicket;
use App\Models\RepairTicketUpdate;
use App\Models\User;
use App\Services\GitPullService;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrators_can_access_system_maintenance(): void
    {
        Role::findOrCreate('member', 'web');
        Role::findOrCreate('admin', 'web');
        $member = User::factory()->create();
        $member->assignRole('member');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.maintenance.index'))
            ->assertOk()
            ->assertSee('System maintenance')
            ->assertSee('Build assets')
            ->assertSee('Run selected seeders')
            ->assertSee('This does not drop tables or clear existing data.')
            ->assertSee('Run pending migrations')
            ->assertSee('Fresh migrate & seed')
            ->assertSee('Optimize clear')
            ->assertSee('Create storage link')
            ->assertSee('Clear repair ticket photos')
            ->assertSee('Cafe POS project')
            ->assertSee('Install Composer dependencies')
            ->assertSee('Git pull');
    }

    public function test_members_can_view_and_run_system_maintenance(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get(route('admin.maintenance.index'))
            ->assertOk()
            ->assertSee('System maintenance')
            ->assertSee(route('admin.backups.index'));

        Artisan::shouldReceive('call')
            ->once()
            ->with('optimize:clear', [])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Cached events cleared.');

        $this->post(route('admin.maintenance.optimize-clear'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Laravel optimization caches cleared.')
            ->assertSessionHas('maintenance_output', 'Cached events cleared.');
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

    public function test_administrator_can_run_only_selected_seeders(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Artisan::shouldReceive('call')
            ->once()
            ->with('db:seed', [
                '--class' => \Database\Seeders\PlannerRoleSeeder::class,
                '--force' => true,
            ])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Planner roles and permissions seeded.');

        Artisan::shouldReceive('call')
            ->once()
            ->with('db:seed', [
                '--class' => \Database\Seeders\DepartmentTeamSeeder::class,
                '--force' => true,
            ])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Departments and teams seeded.');

        Artisan::shouldReceive('call')
            ->once()
            ->with('db:seed', [
                '--class' => \Database\Seeders\CafePosProjectSeeder::class,
                '--force' => true,
            ])
            ->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('Cafe POS project seeded.');

        $this->actingAs($admin)->post(route('admin.maintenance.seed'), [
            'seeders' => [
                \Database\Seeders\PlannerRoleSeeder::class,
                \Database\Seeders\DepartmentTeamSeeder::class,
                \Database\Seeders\CafePosProjectSeeder::class,
            ],
        ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Selected seeders completed: Planner roles and permissions, Departments and teams, Cafe POS project.')
            ->assertSessionHas('maintenance_output', implode("\n\n", [
                \Database\Seeders\PlannerRoleSeeder::class."\nPlanner roles and permissions seeded.",
                \Database\Seeders\DepartmentTeamSeeder::class."\nDepartments and teams seeded.",
                \Database\Seeders\CafePosProjectSeeder::class."\nCafe POS project seeded.",
            ]));
    }

    public function test_administrator_cannot_run_a_seeder_outside_the_allowlist(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Artisan::shouldReceive('call')->never();

        $this->actingAs($admin)->from(route('admin.maintenance.index'))
            ->post(route('admin.maintenance.seed'), [
                'seeders' => ['App\\Seeders\\UnexpectedSeeder'],
            ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHasErrors('seeders.0');
    }

    public function test_selected_seeder_failure_is_reported_and_stops_later_seeders(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Artisan::shouldReceive('call')
            ->once()
            ->with('db:seed', [
                '--class' => \Database\Seeders\PlannerRoleSeeder::class,
                '--force' => true,
            ])
            ->andReturn(1);
        Artisan::shouldReceive('output')->once()->andReturn('Seeder execution failed.');

        Artisan::shouldReceive('call')->never()->with('db:seed', [
            '--class' => \Database\Seeders\DepartmentTeamSeeder::class,
            '--force' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.maintenance.seed'), [
            'seeders' => [
                \Database\Seeders\PlannerRoleSeeder::class,
                \Database\Seeders\DepartmentTeamSeeder::class,
            ],
        ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_error', 'A selected seeder failed. Any earlier selected seeders may already have completed; review the output and application logs.')
            ->assertSessionHas('maintenance_output', \Database\Seeders\PlannerRoleSeeder::class."\nSeeder execution failed.");
    }

    public function test_administrator_can_confirm_existing_public_storage_link(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.maintenance.storage-link'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'The public storage link is already configured.');
    }

    public function test_administrator_can_clear_repair_ticket_photos_without_affecting_other_uploads(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Storage::fake('local');

        $ticketPhoto = $this->createAttachment(RepairTicket::class, 'attachments/repair_tickets/ticket.png', 'image/png');
        $stepPhoto = $this->createAttachment(RepairTicketUpdate::class, 'attachments/tickets/step.webp', 'image/webp');
        $ticketDocument = $this->createAttachment(RepairTicket::class, 'attachments/repair_tickets/manual.pdf', 'application/pdf');
        $otherPhoto = $this->createAttachment('App\\Models\\Project', 'attachments/projects/diagram.png', 'image/png');
        foreach ([$ticketPhoto, $stepPhoto, $ticketDocument, $otherPhoto] as $attachment) {
            Storage::disk('local')->put($attachment->path, 'uploaded file');
        }

        $this->actingAs($admin)->from(route('admin.maintenance.index'))
            ->post(route('admin.maintenance.clear-repair-ticket-photos'), [
                'confirmation' => 'yes',
            ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHasErrors('confirmation');
        Storage::disk('local')->assertExists($ticketPhoto->path);
        $this->assertDatabaseHas('attachments', ['id' => $ticketPhoto->id]);

        $this->actingAs($admin)->post(route('admin.maintenance.clear-repair-ticket-photos'), [
            'confirmation' => 'DELETE REPAIR TICKET PHOTOS',
        ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', '2 repair-ticket photo attachments cleared. Ticket records and user accounts were not changed.');

        Storage::disk('local')->assertMissing($ticketPhoto->path);
        Storage::disk('local')->assertMissing($stepPhoto->path);
        Storage::disk('local')->assertExists($ticketDocument->path);
        Storage::disk('local')->assertExists($otherPhoto->path);
        $this->assertDatabaseMissing('attachments', ['id' => $ticketPhoto->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $stepPhoto->id]);
        $this->assertDatabaseHas('attachments', ['id' => $ticketDocument->id]);
        $this->assertDatabaseHas('attachments', ['id' => $otherPhoto->id]);
    }

    public function test_repair_ticket_photo_cleanup_rejects_unexpected_storage_paths(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Storage::fake('local');
        $attachment = $this->createAttachment(
            RepairTicketUpdate::class,
            'attachments/repair_ticket_updates/../../projects/keep.png',
            'image/png',
        );
        Storage::disk('local')->put('attachments/projects/keep.png', 'keep this file');

        $this->actingAs($admin)->post(route('admin.maintenance.clear-repair-ticket-photos'), [
            'confirmation' => 'DELETE REPAIR TICKET PHOTOS',
        ])->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_error', '0 repair-ticket photo attachments were cleared; 1 could not be fully removed. Review the application logs and retry.');

        Storage::disk('local')->assertExists('attachments/projects/keep.png');
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
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

    public function test_administrator_can_install_production_composer_dependencies(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Process::fake([
            '*' => Process::result('Composer dependencies installed.'),
        ]);

        $this->actingAs($admin)->post(route('admin.maintenance.composer-install'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_success', 'Production Composer dependencies installed successfully.')
            ->assertSessionHas('maintenance_output', 'Composer dependencies installed.');

        Process::assertRan(fn ($process): bool => $process->command === [
            'composer',
            'install',
            '--no-dev',
            '--prefer-dist',
            '--optimize-autoloader',
            '--no-interaction',
            '--no-progress',
        ]);
    }

    public function test_failed_composer_install_shows_command_output(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Process::fake([
            '*' => Process::result('', 'Composer is not available to the web server.', 127),
        ]);

        $this->actingAs($admin)->post(route('admin.maintenance.composer-install'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('maintenance_error')
            ->assertSessionHas('maintenance_output', 'Composer is not available to the web server.');
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

    private function createAttachment(string $attachableType, string $path, string $mimeType): Attachment
    {
        return Attachment::query()->forceCreate([
            'attachable_type' => $attachableType,
            'attachable_id' => 1,
            'path' => $path,
            'original_name' => basename($path),
            'mime_type' => $mimeType,
            'size' => 13,
        ]);
    }
}
