<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_backup_uses_the_active_database_and_includes_account_and_permission_tables(): void
    {
        $this->seed(PlannerRoleSeeder::class);

        $this->assertSame(
            [config('database.default')],
            config('backup.backup.source.databases'),
        );

        foreach ([
            'users',
            'roles',
            'permissions',
            'model_has_roles',
            'model_has_permissions',
            'role_has_permissions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected backup table [{$table}] to exist.");
        }

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $admin->id,
            'model_type' => User::class,
        ]);
    }

    public function test_only_planner_administrators_can_view_and_download_backups(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        Storage::fake('backup_test');
        config([
            'backup.backup.name' => 'backup-test-app',
            'backup.backup.destination.disks' => ['backup_test'],
        ]);

        $member = User::factory()->create();
        $member->assignRole('member');
        $this->actingAs($member)->get(route('admin.backups.index'))->assertForbidden();
        $this->post(route('admin.backups.export-tables'))->assertForbidden();
        $this->get(route('admin.backups.download', ['disk' => 'backup_test', 'backup' => 'private.zip']))
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee('Backups')
            ->assertSee(route('admin.backups.index'))
            ->assertSee('backup-test-app')
            ->assertSee('Storage available')
            ->assertSee('Export selected database tables')
            ->assertSee('name="tables[]"', false)
            ->assertSee('users')
            ->assertSee('user accounts, roles, permissions, role assignments, and direct permission assignments')
            ->assertSee('No backup archives found on this disk.')
            ->assertSee('Age: unavailable until a backup archive exists.')
            ->assertSee('Run backup now');

        $this->get(route('admin.backups.download', ['disk' => 'backup_test', 'backup' => 'private.zip']))
            ->assertNotFound();
        $this->get(route('admin.backups.download', ['disk' => 'local', 'backup' => 'private.zip']))
            ->assertNotFound();
    }

    public function test_planner_administrator_can_see_and_download_a_private_archive(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        Storage::fake('backup_test');
        config([
            'backup.backup.name' => 'backup-test-app',
            'backup.backup.destination.disks' => ['backup_test'],
        ]);

        $filename = 'backup-test-app-2026-10-17-120000.zip';
        $temporaryArchive = tempnam(sys_get_temp_dir(), 'backup-test-');
        $zip = new \ZipArchive;
        $this->assertNotFalse($temporaryArchive);
        $this->assertSame(true, $zip->open($temporaryArchive, \ZipArchive::CREATE));
        $zip->addFromString('backup.txt', 'test archive');
        $zip->close();
        Storage::disk('backup_test')->put(
            'backup-test-app/'.$filename,
            file_get_contents($temporaryArchive),
        );
        unlink($temporaryArchive);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.backups.index'))
            ->assertOk()
            ->assertSee($filename)
            ->assertSee('ago')
            ->assertSee('Download');
        $this->get(route('admin.backups.download', ['disk' => 'backup_test', 'backup' => $filename]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip');
    }

    public function test_planner_administrator_can_export_only_selected_database_tables(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create(['name' => 'Table Export Test']);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.backups.export-tables'), [
            'tables' => ['users'],
        ]);

        $response->assertOk()
            ->assertHeader('content-type', 'application/zip');

        $archivePath = tempnam(sys_get_temp_dir(), 'selected-export-test-');
        $this->assertNotFalse($archivePath);
        file_put_contents($archivePath, $response->streamedContent());

        $zip = new \ZipArchive;
        $this->assertSame(true, $zip->open($archivePath));
        $this->assertNotFalse($zip->locateName('tables/users.csv'));
        $this->assertFalse($zip->locateName('tables/roles.csv'));
        $csv = $zip->getFromName('tables/users.csv');
        $manifest = $zip->getFromName('manifest.json');
        $zip->close();
        unlink($archivePath);

        $this->assertIsString($csv);
        $this->assertStringContainsString('email', $csv);
        $this->assertStringContainsString('Table Export Test', $csv);
        $this->assertIsString($manifest);
        $this->assertSame(['users'], array_keys(json_decode($manifest, true, flags: JSON_THROW_ON_ERROR)['tables']));
    }

    public function test_selected_database_export_rejects_unknown_tables(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->from(route('admin.backups.index'))
            ->post(route('admin.backups.export-tables'), [
                'tables' => ['users; DROP TABLE users'],
            ])->assertRedirect(route('admin.backups.index'))
            ->assertSessionHasErrors('tables.0');
    }
}
