<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_events')) {
            if (Schema::hasTable('events')) {
                throw new RuntimeException('Both project_events and events tables exist; refusing to overwrite either table.');
            }

            Schema::rename('project_events', 'events');
        }

        $foreignKey = DB::connection()->getDriverName() === 'sqlite'
            ? ['project_id']
            : 'project_events_project_id_foreign';

        Schema::table('events', function (Blueprint $table) use ($foreignKey): void {
            $table->dropForeign($foreignKey);
            $table->foreignId('project_id')->nullable()->change();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropForeign(['project_id']);
            $table->foreignId('project_id')->nullable(false)->change();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
        });

        Schema::rename('events', 'project_events');
    }
};
