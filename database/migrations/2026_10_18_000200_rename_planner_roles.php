<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $roleColumn = $columnNames['role_pivot_key'] ?? 'role_id';
        $teamColumn = $columnNames['team_foreign_key'] ?? 'team_id';
        $hasTeams = Schema::hasColumn($tableNames['roles'], $teamColumn);

        DB::transaction(function () use ($tableNames, $roleColumn, $teamColumn, $hasTeams): void {
            foreach (['planner-admin' => 'admin', 'planner-member' => 'member'] as $oldName => $newName) {
                $legacyRoles = DB::table($tableNames['roles'])
                    ->where('name', $oldName)
                    ->where('guard_name', 'web')
                    ->get();

                foreach ($legacyRoles as $legacyRole) {
                    $targetQuery = DB::table($tableNames['roles'])
                        ->where('name', $newName)
                        ->where('guard_name', $legacyRole->guard_name);

                    if ($hasTeams) {
                        $targetQuery->where($teamColumn, $legacyRole->{$teamColumn});
                    }

                    $targetRole = $targetQuery->first();
                    if (! $targetRole) {
                        DB::table($tableNames['roles'])
                            ->where('id', $legacyRole->id)
                            ->update(['name' => $newName]);

                        continue;
                    }

                    $this->mergeRoleAssignments($tableNames, $roleColumn, $legacyRole->id, $targetRole->id);
                    DB::table($tableNames['roles'])->where('id', $legacyRole->id)->delete();
                }
            }
        });

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function mergeRoleAssignments(array $tableNames, string $roleColumn, int $legacyRoleId, int $targetRoleId): void
    {
        foreach (DB::table($tableNames['role_has_permissions'])->where($roleColumn, $legacyRoleId)->get() as $assignment) {
            $values = (array) $assignment;
            $values[$roleColumn] = $targetRoleId;
            DB::table($tableNames['role_has_permissions'])->insertOrIgnore($values);
        }
        DB::table($tableNames['role_has_permissions'])->where($roleColumn, $legacyRoleId)->delete();

        foreach (DB::table($tableNames['model_has_roles'])->where($roleColumn, $legacyRoleId)->get() as $assignment) {
            $values = (array) $assignment;
            $values[$roleColumn] = $targetRoleId;
            DB::table($tableNames['model_has_roles'])->insertOrIgnore($values);
        }
        DB::table($tableNames['model_has_roles'])->where($roleColumn, $legacyRoleId)->delete();
    }

    public function down(): void
    {
        // Role names are managed data and may have been edited since this migration ran.
    }
};
