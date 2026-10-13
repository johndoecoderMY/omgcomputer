<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ticket_number', 40)->unique();
            $table->string('customer_name', 180);
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('device_type', 80);
            $table->string('device_model', 180)->nullable();
            $table->string('serial_number', 120)->nullable();
            $table->string('subject', 180);
            $table->text('issue_description');
            $table->text('diagnostic_notes')->nullable();
            $table->string('status', 32)->default('received');
            $table->string('priority', 24)->default('normal');
            $table->decimal('quoted_amount', 10, 2)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['assigned_to', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_tickets');
    }
};
