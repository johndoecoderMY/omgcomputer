<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['department_id', 'name']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('inventory_parts', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->after('sku')->constrained()->nullOnDelete();
        });

        Schema::table('flow_charts', function (Blueprint $table): void {
            $table->string('category', 20)->default('website')->after('title');
            $table->string('subject_name')->nullable()->after('category');
        });

        Schema::create('flow_chart_project', function (Blueprint $table): void {
            $table->foreignId('flow_chart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['flow_chart_id', 'project_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_chart_project');
        Schema::table('flow_charts', function (Blueprint $table): void {
            $table->dropColumn(['category', 'subject_name']);
        });
        Schema::table('inventory_parts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supplier_id');
        });
        Schema::dropIfExists('suppliers');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('team_id');
        });
        Schema::dropIfExists('teams');
        Schema::dropIfExists('departments');
    }
};
