<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('project_activities')
                ->whereColumn('project_activities.project_id', 'projects.id'))
            ->orderBy('id')
            ->chunkById(500, function ($projects): void {
                $activities = $projects->map(fn (object $project): array => [
                    'project_id' => $project->id,
                    'user_id' => $project->created_by_user_id,
                    'summary' => 'Project created (historical record)',
                    'details' => json_encode(
                        ['Project creation is known; older changes cannot be reconstructed.'],
                        JSON_THROW_ON_ERROR,
                    ),
                    'created_at' => $project->created_at,
                    'updated_at' => $project->created_at,
                ])->all();

                if ($activities !== []) {
                    DB::table('project_activities')->insert($activities);
                }
            });
    }

    public function down(): void
    {
        DB::table('project_activities')
            ->whereIn('summary', ['Project created (historical record)', 'Project history initialized'])
            ->delete();
    }
};
