<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planning_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('category', 24)->default('task');
            $table->string('status', 24)->default('backlog');
            $table->string('priority', 24)->default('medium');
            $table->string('visibility', 24)->default('personal');
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index(['visibility', 'status']);
            $table->index(['category', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planning_tasks');
    }
};
