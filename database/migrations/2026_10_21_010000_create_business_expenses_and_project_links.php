<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_expenses')) {
            Schema::create('business_expenses', function (Blueprint $table): void {
                $table->id();
                $table->string('category', 24);
                $table->string('name');
                $table->string('vendor')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('frequency', 24)->default('once');
                $table->date('due_date')->nullable();
                $table->decimal('cycle_paid_amount', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamps();
                $table->index(['category', 'due_date'], 'business_expense_category_due_idx');
            });
        }

        if (! Schema::hasTable('business_expense_payments')) {
            Schema::create('business_expense_payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('business_expense_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->date('paid_at');
                $table->date('period_due_date')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasIndex('business_expense_payments', 'business_expense_period_idx')) {
            Schema::table('business_expense_payments', function (Blueprint $table): void {
                $table->index(['business_expense_id', 'period_due_date'], 'business_expense_period_idx');
            });
        }

        if (! Schema::hasTable('project_links')) {
            Schema::create('project_links', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->string('label', 120);
                $table->string('url', 2048);
                $table->boolean('visible_to_customer')->default(false);
                $table->timestamps();
                $table->index(['project_id', 'visible_to_customer'], 'project_links_visibility_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_links');
        Schema::dropIfExists('business_expense_payments');
        Schema::dropIfExists('business_expenses');
    }
};
