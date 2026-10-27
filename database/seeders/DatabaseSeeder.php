<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedAccessControl();
        $this->seedLocalAdministrator();
        $this->seedBaselineApplicationData();

        if (app()->environment(['local', 'testing'])) {
            $this->seedLocalDemoData();
        }
    }

    private function seedAccessControl(): void
    {
        $this->call(PlannerRoleSeeder::class);
    }

    private function seedLocalAdministrator(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $initialPassword = config('auth.seed_admin_password');
        if (! is_string($initialPassword) || strlen($initialPassword) < 12) {
            throw new RuntimeException('Set SEED_ADMIN_PASSWORD to a value of at least 12 characters before seeding a local or testing database.');
        }

        $admin = User::query()->where('email', 'support@omgcomputer.my')->first();
        $legacyAdmin = false;
        if (! $admin) {
            $admin = User::query()->where('email', 'test@example.com')->first();
            $legacyAdmin = (bool) $admin;
        }

        $isNewAdmin = ! $admin;
        $admin ??= new User;
        $attributes = [
            'name' => $admin->name ?: 'Test Admin',
            'email' => 'support@omgcomputer.my',
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ];
        if ($isNewAdmin || $legacyAdmin) {
            $attributes['password'] = Hash::make($initialPassword);
            $attributes['force_password_change'] = true;
        }

        $admin->forceFill($attributes)->save();
        $admin->syncRoles('admin');
    }

    private function seedBaselineApplicationData(): void
    {
        $this->call([
            DepartmentTeamSeeder::class,
            OmgComputerProjectSeeder::class,
            CustomerMessageTemplateSeeder::class,
            ServiceSopExampleSeeder::class,
            StudentFypSopSeeder::class,
            SoftwareStructureSopSeeder::class,
            DeveloperSetupFlowChartSeeder::class,
        ]);
    }

    private function seedLocalDemoData(): void
    {
        $this->call([
            PcPartsSeeder::class,
            ReadyBuiltPcSeeder::class,
            DemoDataSeeder::class,
            BusinessPlanTaskSeeder::class,
            RentalExpenseSeeder::class,
        ]);
    }
}
