<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_projects', function (Blueprint $table): void {
            $table->string('slug', 80)->nullable()->unique();
        });

        $projects = DB::table('planning_projects')->orderBy('id')->get();
        $primary = $projects->first();

        if ($primary !== null) {
            DB::table('planning_projects')->where('id', $primary->id)->update([
                'slug' => 'omg-computer',
                'name' => 'OMG Computer',
                'description' => 'The OMG Computer website, repair shop, and day-to-day operations.',
                'visibility' => 'team',
            ]);

            DB::table('planning_tasks')->update(['project_id' => $primary->id]);

            DB::table('planning_projects')
                ->where('id', '!=', $primary->id)
                ->delete();

            return;
        }

        $firstUser = DB::table('users')->orderBy('id')->first();

        if ($firstUser !== null) {
            $projectId = DB::table('planning_projects')->insertGetId([
                'user_id' => $firstUser->id,
                'slug' => 'omg-computer',
                'name' => 'OMG Computer',
                'description' => 'The OMG Computer website, repair shop, and day-to-day operations.',
                'visibility' => 'team',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('planning_tasks')->update(['project_id' => $projectId]);
        }
    }

    public function down(): void
    {
        Schema::table('planning_projects', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
