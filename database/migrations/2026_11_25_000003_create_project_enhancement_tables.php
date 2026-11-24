<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('category', 40);
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('scope_in')->nullable();
            $table->text('scope_out')->nullable();
            $table->json('modules')->nullable();
            $table->json('milestones')->nullable();
            $table->json('checklist_items')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('project_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('customer_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'status']);
        });

        Schema::create('project_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('category', 40)->default('other');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->boolean('visible_to_customer')->default(false);
            $table->date('document_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'category']);
        });

        Schema::create('project_feedback', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['project_id', 'customer_user_id']);
        });

        Schema::create('project_checklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_checklist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('project_risks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('probability', 20)->default('medium');
            $table->string('impact', 20)->default('medium');
            $table->text('mitigation')->nullable();
            $table->string('status', 20)->default('open');
            $table->date('due_on')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'status']);
        });

        Schema::create('project_retention_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('retention_days')->nullable();
            $table->string('action', 20)->default('archive');
            $table->boolean('legal_hold')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_time_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('worked_on');
            $table->decimal('hours', 8, 2);
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->boolean('billable')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('project_cost_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40)->default('other');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('incurred_on');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_cost_entries');
        Schema::dropIfExists('project_time_entries');
        Schema::dropIfExists('project_retention_policies');
        Schema::dropIfExists('project_risks');
        Schema::dropIfExists('project_checklist_items');
        Schema::dropIfExists('project_checklists');
        Schema::dropIfExists('project_feedback');
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_approvals');
        Schema::dropIfExists('project_templates');
    }
};
