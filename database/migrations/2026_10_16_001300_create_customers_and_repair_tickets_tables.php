<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('phone', 64)->nullable();
            $table->string('company')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'name']);
        });

        Schema::create('repair_tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_type', 80)->nullable();
            $table->string('device_model')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('issue_description');
            $table->text('diagnosis')->nullable();
            $table->string('status', 32)->default('new');
            $table->string('priority', 24)->default('normal');
            $table->date('due_at')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_tickets');
        Schema::dropIfExists('customers');
    }
};