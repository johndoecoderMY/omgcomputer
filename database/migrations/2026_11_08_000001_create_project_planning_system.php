<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->text('business_background')->nullable();
            $table->text('user_roles')->nullable();
            $table->text('objectives')->nullable();
            $table->text('problem_statement')->nullable();
            $table->text('proposed_solution')->nullable();
            $table->text('scope_in')->nullable();
            $table->text('scope_out')->nullable();
            $table->string('planning_status', 24)->default('draft');
            $table->date('start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
        });

        Schema::create('project_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        Schema::table('project_requirements', function (Blueprint $table): void {
            $table->string('code', 32)->nullable();
            $table->string('requirement_type', 24)->default('functional');
            $table->string('priority', 16)->default('must');
            $table->string('status', 24)->default('planned');
            $table->text('user_story')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->foreignId('project_module_id')->nullable()->constrained('project_modules')->nullOnDelete();
            $table->foreignId('parent_requirement_id')->nullable()->constrained('project_requirements')->nullOnDelete();
            $table->index(['project_id', 'requirement_type', 'status']);
        });
        DB::table('project_requirements')
            ->where('is_completed', true)
            ->update(['status' => 'done']);

        Schema::create('project_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_module_id')->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('priority', 16)->default('must');
            $table->string('status', 24)->default('planned');
            $table->timestamps();
            $table->unique(['project_module_id', 'name']);
        });

        Schema::create('project_milestones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('depends_on_milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->text('deliverable')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('due_on')->nullable();
            $table->string('status', 24)->default('planned');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['project_id', 'status', 'due_on']);
        });

        Schema::create('project_quality_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_requirement_id')->nullable()->constrained('project_requirements')->nullOnDelete();
            $table->string('kind', 16);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->text('expected_result')->nullable();
            $table->text('actual_result')->nullable();
            $table->string('priority', 16)->default('medium');
            $table->string('severity', 16)->nullable();
            $table->string('status', 24)->default('not_run');
            $table->text('resolution')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'kind', 'status']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('project_requirement_id')->nullable()->constrained('project_requirements')->nullOnDelete();
            $table->foreignId('project_feature_id')->nullable()->constrained('project_features')->nullOnDelete();
            $table->foreignId('project_milestone_id')->nullable()->constrained('project_milestones')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('project_requirement_id');
            $table->dropConstrainedForeignId('project_feature_id');
            $table->dropConstrainedForeignId('project_milestone_id');
        });

        Schema::dropIfExists('project_quality_items');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('project_features');

        Schema::table('project_requirements', function (Blueprint $table): void {
            $table->dropIndex(['project_id', 'requirement_type', 'status']);
            $table->dropConstrainedForeignId('project_module_id');
            $table->dropConstrainedForeignId('parent_requirement_id');
            $table->dropColumn([
                'code',
                'requirement_type',
                'priority',
                'status',
                'user_story',
                'acceptance_criteria',
            ]);
        });

        Schema::dropIfExists('project_modules');

        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn([
                'business_background',
                'user_roles',
                'objectives',
                'problem_statement',
                'proposed_solution',
                'scope_in',
                'scope_out',
                'planning_status',
                'start_date',
                'target_end_date',
                'budget',
            ]);
        });
    }
};
