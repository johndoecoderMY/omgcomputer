<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 24)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 64)->nullable();
            $table->string('service_id', 80);
            $table->string('service_name');
            $table->string('device_model')->nullable();
            $table->text('request_notes')->nullable();
            $table->timestamp('starts_at')->unique();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('status', 24)->default('pending');
            $table->timestamps();
            $table->index(['status', 'starts_at']);
            $table->index(['assigned_to', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};