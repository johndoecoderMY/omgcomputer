<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planning_tasks', function (Blueprint $table): void {
            $table->foreignId('project_id')
                ->nullable()
                ->after('user_id')
                ->constrained('planning_projects')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('planning_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_id');
        });
    }
};
