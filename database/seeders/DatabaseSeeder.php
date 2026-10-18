<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlannerRoleSeeder::class);

        if (app()->environment(['local', 'testing'])) {
            foreach ([
                ['name' => 'Test Admin', 'email' => 'test@example.com', 'role' => 'admin'],
                ['name' => 'Test Member', 'email' => 'member@example.com', 'role' => 'member'],
                ['name' => 'Test Customer', 'email' => 'customer@example.com', 'role' => 'customer'],
            ] as $account) {
                $user = User::firstOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $account['name'],
                        'password' => Hash::make('password'),
                        'force_password_change' => false,
                    ],
                );
                if (Hash::check('password', $user->password) && ! $user->force_password_change) {
                    // $user->forceFill(['force_password_change' => true])->save();
                }
                $user->syncRoles($account['role']);
            }
        }

        $this->call(DepartmentTeamSeeder::class);
        $this->call(OmgComputerProjectSeeder::class);
        $this->call(CustomerMessageTemplateSeeder::class);
        $this->call(ServiceSopExampleSeeder::class);
        $this->call(StudentFypSopSeeder::class);
        $this->call(SoftwareStructureSopSeeder::class);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoDataSeeder::class);
            $this->call(BusinessPlanTaskSeeder::class);
            $this->call(RepairTicketExampleSeeder::class);
        }
    }
}
