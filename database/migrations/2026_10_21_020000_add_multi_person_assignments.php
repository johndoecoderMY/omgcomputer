<?php

use App\Models\RepairTicket;
use App\Models\Task;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('project_id')->nullable()->change();
        });

        Schema::create('user_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('assignable');
            $table->timestamps();
            $table->unique(['assignable_type', 'assignable_id', 'user_id'], 'user_assignments_unique');
        });

        foreach ([
            'tasks' => Task::class,
            'repair_tickets' => RepairTicket::class,
        ] as $table => $model) {
            DB::table($table)
                ->whereNotNull('assigned_to')
                ->orderBy('id')
                ->each(function (object $record) use ($model): void {
                    DB::table('user_assignments')->insert([
                        'user_id' => $record->assigned_to,
                        'assignable_type' => $model,
                        'assignable_id' => $record->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_assignments');
    }
};
